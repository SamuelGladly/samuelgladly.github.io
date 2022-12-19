const express = require('express');
// import express from 'express';

// import { getPosts, getPost, createPost, updatePost, likePost, deletePost } from '../controllers/posts.js';
const { getPosts, getPost, createPost, likePost, deletePost, getPostsBySearch, getPostsByTag, commentOnPost, savePost, updatePost, getTrendingPosts} = require('../controllers/posts.js');

const { Authenticate } = require('../middleware/auth.js');
const router = express.Router();

// router.get("/", (req, res) => {
//     res.send("Hello World");z
// })
router.get('/acout', (req,res) => {
    res.send('Hello from user route');
});


// router.get('/', getPosts);
// router.post('/', createPost);
// router.get('/:id', getPost);
// router.patch('/:id', updatePost);
// router.delete('/:id', deletePost);
// router.patch('/:id/likePost', likePost);

router.route("/search").get(getPostsBySearch)

router.route("/").get(getPosts);
router.route("/trending/post").get(getTrendingPosts);
router.route("/").post(Authenticate ,createPost)

router.route("/:id").get(getPost)


router.route("/:id").patch(Authenticate  ,updatePost)


router.route("/:id").delete(deletePost)
router.route("/:id/likePost").patch(likePost)

router.route("/tag/:tag").get(getPostsByTag);

router.route("/:id/comment").post(commentOnPost);

router.route('/save').post(Authenticate, savePost)

// export default router;


module.exports = router;