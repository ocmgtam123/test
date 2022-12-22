const express = require("express");
const cors = require('cors');
const app = express();
const mongoose = require('mongoose');
const bodyParser = require('body-parser');
const dotenv = require('dotenv');
const port = process.env.PORT || 3500;
const server = require("http").createServer(app);

dotenv.config();
//connect database mongodb
mongoose.set('strictQuery', true);
mongoose.connect("mongodb+srv://database_test:xbk3kr8NJV7S3gYC@clusterdatabasetest.0lqstqf.mongodb.net/database_test?retryWrites=true&w=majority", () => {
    console.log("Connected to mongodb mongodb+srv://database_test:xbk3kr8NJV7S3gYC@clusterdatabasetest.0lqstqf.mongodb.net/database_test?retryWrites=true&w=majority");
});
app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());
app.use(cors());

const userRoute = require("./routes/user");
app.use("/user", userRoute);
app.get("/", (req,res)=>{
    res.send("Hello");
});

server.listen(port, (req, res) => {
    console.log("Server is runing port: " + port);
})