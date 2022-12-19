const express = require('express');
const { login, register, getProfile, logout, getMyPosts, getAllUsers, getUserProfile, getUserPosts, updateProfile, getMySavedPost, forgotPassword, resetPassword, updatePassword, googleAuth } = require('../controllers/user');
const { Authenticate } = require('../middleware/auth');
// const { register, login, logout, myprofile, updateProfile, getUserProfile } = require('../controllers/userController');
// const {Authenticate} = require('../middleware/Authenticate');



const router = express.Router();

router.route("/login").post(login);

router.route('/googleauth').post(googleAuth);

router.route("/register").post(register);

router.route("/logout").get(logout);

router.route("/me").get( Authenticate,getProfile);

router.route("/my/posts").get(Authenticate, getMyPosts);

router.route("/allusers").get(getAllUsers);

router.route("/:id").get(getUserProfile);

router.route("/userpost/:id").get(getUserPosts);

router.route("/update/profile").put(Authenticate, updateProfile);

router.route("/saved/posts").get(Authenticate, getMySavedPost);
// router.route('/update').post(Authenticate, updateProfile);

// router.route("/user/:id").get(getUserProfile);

router.route("/forgot/password").post(forgotPassword);

router.route("/password/reset/:token").put(resetPassword);

router.route("/update/password").put(Authenticate, updatePassword);



module.exports = router;