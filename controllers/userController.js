const { User } = require("../models/model");
const bcrypt = require('bcrypt');

const userController = {
    add: async (req, res) => {
        try {
            const result1 = await User.findOne({username: req.body.username}).count();
            if(result1==0){
                const newUser = new User(req.body);
                newUser.password = bcrypt.hashSync(req.body.password, 10);
                const saved = await newUser.save();
                res.status(200).json(saved);
            }else{
                res.send({success:false, message:"username exit system"});
            }          

        } catch (err) {
            res.status(500).json(err);
        }
    },
    getAll: async (req, res) => {
        try {
            res.send("aaaaa");
            //const user = await User.find();
            //res.status(200).json(user);
        } catch (err) {
            res.status(500).json(err);
        }
    }
};

module.exports = userController;