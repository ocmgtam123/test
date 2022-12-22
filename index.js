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
mongoose.connect((process.env.MONGODB_URL), () => {
    console.log("Connected to mongodb");
});
app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());
app.use(cors());

const userRoute = require("./routes/user");
app.use("/user", userRoute);

server.listen(port, (req, res) => {
    console.log("Server is runing port: " + port);
})