const mongoose = require("mongoose");
const userSchema = new mongoose.Schema({
    username: {
        type: String,
        required: true
    },
    password: String
},{
    collection:"User"
});
let User = mongoose.model("User", userSchema);
module.exports = { User };