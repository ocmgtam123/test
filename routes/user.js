const userController = require("../controllers/userController");
const router = require("express").Router();
router.get("/", userController.getAll);
router.post("/", userController.add);
module.exports = router;