<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Post_image extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('postimage_model');
        $this->load->model('sys_enum_model');
    }
  
    public function get_last_updated_time(){
        $this->check_method('GET');
        $user_data=$this->auth_request();
        if (empty($user_data)) {
            $this->errors(803, 'user_data');
            $this->set_response(NULL, 200);
        }
        $result = $this->postimage_model->select_data(array(
            'fields' => array('default' => FALSE, 'C_TIME'),
            'filter' => array(
                    //'STATUS' => 'A',
                    'IS_THUMB'=>'N'
                ),
            'get_row' => TRUE,
            'limit'=>1,
            'order_by' => array('c_time' => 'DESC', 'image_seq' => 'DESC')
        ));
        $this->set_response(array('data' => array('last_time' => $result['C_TIME'])), 200);
    }
  
    public function get_normal_list(){
        $this->check_method('GET');
        $user_data=$this->auth_request();
        if (empty($user_data)) {
            $this->errors(803, 'user_data');
            $this->set_response(NULL, 200);
        }
        $page = $this->get_data_request("page", TRUE, "GET");
        $limit = $this->get_data_request("limit", TRUE, "GET");
        $order_by = $this->get_data_request("order_by", TRUE, "GET");
        $sorted_by = $this->get_data_request("sorted_by", TRUE, "GET");
        $store_seq = $this->get_data_request("store_seq", TRUE, "GET");
        $style = $this->get_data_request("style_seq", TRUE, "GET");
        $status = $this->get_data_request("image_status", TRUE, "GET");
        $folder_seq = $this->get_data_request("folder_seq", TRUE, "GET");
        $delete_from_date = $this->get_data_request("delete_from_date", TRUE, "GET");
        $delete_to_date = $this->get_data_request("delete_to_date", TRUE, "GET");
        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        }
        if (!is_numeric($limit) || $limit < 1) {
            $limit = config_item('_general')['limit_default'];
        }
        if (!empty($sorted_by)) {
            if (!in_array($sorted_by, array("asc", "desc"))) {
                $this->errors(604, 'sorted_by');
                $this->set_response(NULL, 200);
            }
        } else {
            $sorted_by = "desc";
        }
        if (!empty($order_by)) {
            if (!in_array($order_by, array("image_c_time", "image_d_time", "store_fullname", "folder_name"))) {
                $this->errors(604, 'order_by');
                $this->set_response(NULL, 200);
            }
            if($order_by=="image_c_time"){
                $order_by="c_time";
            }
            if($order_by=="image_d_time"){
                $order_by="d_time";
            }
        } else {
            $order_by = "SEQ";
        }
        $filter_out=array('is_thumb'=>'N');
        if (!empty($status)) {
            if (!in_array($status, array("A", "W", "D", "P", "R"))) {
                $this->errors(604, 'status');
                $this->set_response(NULL, 200);
            }
            $filter_out['status']=$status;
        }
        if (!empty($folder_seq)) {
            $filter_out['folder_seq']=$folder_seq;
        }
        if (!empty($store_seq)) {
            $store_seq_w = "";
            foreach ($store_seq as $key => $v) {
                if ($v != "") {
                    $store_seq_w.=",".$v;
                }
            }
            $store_seq_w=substr($store_seq_w,1);
            $filter_out['wheresql']= 'storeseq IN ('.$store_seq_w.')';
        }
        if (!empty($style)) {
            $style_w = "";
            foreach ($style as $key => $v) {
                if ($v != "") {
                    $style_w.="OR style_id_name_str LIKE '%".$v."%' ";
                }
            }
            $style_w=substr($style_w,2);
            $filter_out['wheresql']= '('.$style_w.')';
        }
        if (!empty($delete_from_date)&&!empty($delete_to_date)) {
            $filter_out['wheresql']= 'PI.D_TIME BETWEEN "'.$delete_from_date.' 00:00:00" AND "'.$delete_to_date.' 23:59:59"';
        }
        //print_r($filter_out);die;
        $this->load->model("postimage_model");
        $result = $this->postimage_model->select_data(array(
            'fields' => array('default' => FALSE,"POST_SEQ","FOLDER_SEQ","IMAGE","PI.C_TIME","PI.D_TIME","PI.STATUS","store_fullname"),
            'join' => array(
                'folder_image' => array(
                    'model' => 'folder_image',
                    'type' => 'query',
                    'join_type' => 'left',
                    'join_key' => array(array('SEQ', 'FOLDER_SEQ')),
                    'fields' => array('default' => FALSE, 'FOLDER_NAME','STORE_SEQ as storeseq','STORE_NAME as store_fullname'),
                    'order_by' => FALSE,
                ),
                'store_style_ctgry' => array(
                    'model' => 'store_style_ctgry',
                    'type' => 'query',
                    'join_type' => 'left',
                    'join_key' => array(array('STORE_SEQ',array('storeseq'))),
                    'fields' => array('GROUP_CONCAT(CONCAT(SC.SEQ,\'_\',SC.NAME)) AS style_id_name_str', 'default' => FALSE),
                    'join' => array(array('model' => 'style_ctgry', 'type' => 'table', 'join_key' => array(array('SEQ', 'STYLE_CTGRY_SEQ')))),
                    'filter' => array(
                        'multi' => TRUE,
                        'style_ctgry' => array(
                            array('status', 'A'),
                        ),
                    ),
                    'group_by' => 'STORE_SEQ',
                    'order_by' => FALSE
                )
            ),
            'filter' => $filter_out,
            'get_rows' => TRUE,
            'get_total' => TRUE,
            'limit' => $limit,
            'offset' => $limit * $page - $limit,
            'order_by' => $order_by,
            'sorted_by' => $sorted_by
        ));
        //p($this->db->last_query());
        //p($result);die;

		//Get enum
		if($result['rows'])
			$sys_status = get_enum(['TBL_POST_IMAGE.STATUS']);

        $retu = array();
        $list_data = array();
        foreach ($result['rows'] as $row) {
            $arr_style = array();
            if($row['style_id_name_str']!=""){
                $arr = explode(",", $row['style_id_name_str']);
                foreach ($arr as $val) {
                    $style_array = explode("_", $val);
                    array_push($arr_style, array(
                        "seq" => (int) $style_array[0],
                        "name" => $style_array[1]
                    ));
                }
            }
            $image_url = ($row['IMAGE'] != '') ? FILE_SERVER . PRODUCT_IMAGE_LARGE . $row['IMAGE'] : "";

            if ($sys_status && isset($sys_status[$row['STATUS']])) {
				$status_s = $sys_status[$row['STATUS']];
            } else {
                $status_s = $row['STATUS'];
            }

            $retu = array(
                'store_seq' => (int) $row['storeseq'],
                'store_fullname' => $row['store_fullname'],
                'folder_seq' => (int)$row['FOLDER_SEQ'],
                'folder_fullname' => $row['FOLDER_NAME'],
                'image_seq' => (int)$row['SEQ'],
                'image_url' => $image_url,
                'image_status' =>$status_s,
                'image_c_time' => $row['C_TIME'],
                'image_d_time' => $row['D_TIME'],
                'list_style' => $arr_style,
            );
            array_push($list_data, $retu);
        }
        $resp = array(
            'total_cnt' => $result['total'],
            'limit' => $limit,
            'page' => $page,
            'store_seq' => $store_seq,
            'style' => $style,
            'status' => $status,
            'folder_seq' => $folder_seq,
            'delete_from_date' => $delete_from_date,
            'delete_to_date' => $delete_to_date,
            'list_data' => $list_data
        );
        $this->set_response(array('data' => $resp), 200);
    }
<<<<<<< nht.tam

    function update_status_waiting() {
        $this->check_method('POST');
        $this->auth_request();
        $image_seq = $this->get_data_request('image_seq');
        if (empty($image_seq)) {
            $this->errors(600, 'image_seq');
        }
        if (!empty($this->errors)) {
            $this->set_response(NULL, 200);
        }
        $re = $this->updateaction($image_seq, "A", "W");
        $this->set_response(
                array(
            'data' => array(
                'success' => $re['success'],
                'image_sent' => count($image_seq),
                'image_processed' => $re['image_processed'],
            )
                ), 200
        );
    }

    function update_status_deleted() {
        $this->check_method('POST');
        $this->auth_request();
        $image_seq = $this->get_data_request('image_seq');
        if (empty($image_seq)) {
            $this->errors(600, 'image_seq');
        }
        if (!empty($this->errors)) {
            $this->set_response(NULL, 200);
        }
        $re = $this->updateaction($image_seq, "A", "D");
        $this->set_response(
                array(
            'data' => array(
                'success' => $re['success'],
                'image_sent' => count($image_seq),
                'image_processed' => $re['image_processed'],
            )
                ), 200
        );
    }

    function update_status_restore() {
        $this->check_method('POST');
        $this->auth_request();
        $image_seq = $this->get_data_request('image_seq');
        if (empty($image_seq)) {
            $this->errors(600, 'image_seq');
        }
        if (!empty($this->errors)) {
            $this->set_response(NULL, 200);
        }
        $re = $this->updateaction($image_seq, "D", "A");
        $this->set_response(
                array(
            'data' => array(
                'success' => $re['success'],
                'image_sent' => count($image_seq),
                'image_processed' => $re['image_processed'],
            )
                ), 200
        );
    }

    public function updateaction($image_seq, $status, $status_update) {
        $arr_update = array();
        $arr_his = array();
        $arr_folder = array();
        $i = 0;
        foreach ($image_seq as $key => $seq) {
            if ($seq != "") {
                $check = $this->postimage_model->select_data(array(
                    'fields' => array('default' => FALSE, 'FOLDER_SEQ'),
                    'filter' => array('image_seq' => (int) ($seq), 'status' => $status),
                    "order_by" => FALSE,
                    'get_row' => TRUE,
                ));
                if ($check) {
                    $data = array(
                        'SEQ' => (int) ($seq),
                        'STATUS' => $status_update,
                        'M_TIME' => date('Y-m-d H:i:s'),
                        'M_ID' => @$this->user_data['user_id']
                    );
                    $data_his = array(
                        'POST_IMAGE_SEQ' => (int) ($seq),
                        'STATUS' => $status,
                        'C_ID' => @$this->user_data['user_id'],
                        'C_TIME' => date('Y-m-d H:i:s')
                    );
                    array_push($arr_update, $data);
                    array_push($arr_his, $data_his);
                    if (!in_array($check['FOLDER_SEQ'], $arr_folder)) {
                        array_push($arr_folder, (int) $check['FOLDER_SEQ']);
                    }
                    $i++;
                }
            }
        }
        $string="";
        if (!empty($arr_folder)) {
            foreach ($arr_folder as $key => $value) {
                $sql = "SELECT DISTINCT(`STATUS`),FOLDER_SEQ FROM TBL_POST_IMAGE WHERE FOLDER_SEQ = " . $value;
                $row = $this->postimage_model->get_rows_by_sql($sql);
                $string.="_".$value.":";
                foreach ($row as $r) {
                    $string.=$r["STATUS"];
                }                
            }
        }
        $string= substr($string,1);
        $arr_n_s = array("A", "W", "AW");
        $arr_done = array("D", "P", "R","DP","DR","PR","DPR");
        $arr = explode("_", $string);
        $array_update_folder_status=array();
        for($i=0;$i<count($arr);$i++){
            $array_mang= explode(":", $arr[$i]);
            $folder_id=$array_mang[0];
            $math=$array_mang[1];
            if(in_array($math, $arr_n_s)){
                $status_math="N";
            }elseif(in_array($math, $arr_done)){
                $status_math="D";
            }else{
                $status_math="I";
            }
            $data_f_update=array(
                "SEQ"=>$folder_id,
                "STATUS"=>$status_math,
            );
            array_push($array_update_folder_status, $data_f_update);
        }
        $this->postimage_model->update_row($arr_update);        
        $this->load->model("folder_image_model");
        $this->folder_image_model->update_row($array_update_folder_status);        
        $this->load->model("post_image_status_his_model");
        $this->post_image_status_his_model->insert_row($arr_his);
        $result = array(
            'success' => TRUE,
            'image_processed' => $i,
        );
        return $result;
    }

=======
>>>>>>> development
}
