// import express from 'express';
// import mongoose from 'mongoose';

const mongoose = require('mongoose');
const User = require('../models/userM');

const cloudinary = require('cloudinary');
// import PostMessage from '../models/postMessage.js';
const PostMessage = require('../models/postMessage.js');


// Get all posts
exports.getPosts = async (req, res) => { 
    try {
        const posts = await PostMessage.find();
        
                
        res.status(200).json(
            posts
        );
    } catch (error) {
        res.status(404).json({ message: error.message });
    }
}

// Params and Queries are two different things.
// Params are the data that is passed in the url. Used to indentify the resource.
// Example: /api/posts/:id
// Example: / posts/123

// Queries are the data that is passed in the url after the ?. Used to filter the resource.
// Example: /api/posts?search=something
// Example: /api/posts?search=something&tags=somethingelse
// Example: /posts?page=1&limit=10
// Example: /posts?page=1&limit=10&search=something


// Get posts by search
exports.getPostsBySearch = async (req, res) => {

    const { searchQuery, tags } = req.query;
    // console.log(req.query);
    // var serrr = req.query;
    // var setToString = serrr.toString();

    var ter = searchQuery.toLowerCase();
    // console.log(ter);

    try {
        // now lets convert the query to a regular expression
        // the reason we converted the query to a regular expression is :
        // because that way it is easier for mongodb/mongoose to search for the query

        const regex = new RegExp(searchQuery, 'i');
        // i stands for Ignore Case
        // Test test TEST TeSt => test 
        // everyone will be treated in same way
        // console.log(regex);


        const posts = await PostMessage.find({
            $or: [
                { title: regex },
                { message: regex },
                { tags: regex },
                {category : regex},
            ]
        });
        res.status(200).json(
            posts
        )


    }catch(error){
        res.status(404).json({
            message: error.message
        })
    }

    // const { search } = req.params;
    // const { tags } = req.body;
    // const { creator } = req.body;
    // const { title } = req.body;
    // const { message } = req.body;
    // const { selectedFile } = req.body;
    // const { likeCount } = req.body;
    // const { createdAt } = req.body;
    // const { updatedAt } = req.body;
    // const { __v } = req.body;
    // const { _id } = req.body;
    // const { likeCount } = req.body;


    // try {
    //     const postMessages = await PostMessage.find({
    //         $text: {
    //             $search: req.body.search
    //         }
    //     });
    //     res.status(200).json(postMessages);
    // } catch (error) {
    //     res.status(404).json({ message: error.message });
    // }

    // const { search } = req.body;
    // const postMessages = await PostMessage.find({
    //     $text: {
    //         $search: search   
    
    //     }
    // });
    // res.status(200).json(postMessages);

}
// Get post by id
exports.getPost = async (req, res) => { 
    const { id } = req.params;
    if (!mongoose.Types.ObjectId.isValid(id)) return res.status(404).send(`No post with id: ${id}`);


    try {
        const post = await PostMessage.findById(id);
        
        res.status(200).json(post);
    } catch (error) {
        res.status(404).json({ message: error.message });
    }
}

// Create new post
exports.createPost = async (req, res) => {
    try {
    const { title,shortDescription, message, category, tags } = req.body;

    // console.log(req.body);
    // console.log(content);
    const myCloud = await cloudinary.v2.uploader.upload(req.body.image, {
        folder: "posts",
      });

    // const r = req.body;
    // console.log(r);

    const newPostMessage = new PostMessage({
        title, 
        shortDescription,
        message,  
        tags,
        category,
        owner : req.user._id,
        image :{
            public_id: myCloud.public_id,
            url: myCloud.secure_url,
        },
    });

    const user = await User.findById(req.user._id);

    user.posts.unshift(newPostMessage);

    await user.save();

    
        await newPostMessage.save();

        res.status(201).json({
            success : true,
            posts : newPostMessage,
            message : "Post Created",
            
         
        });
    } catch (error) {
        res.status(409).json({
            success : false, 
            message: error.message 
        });
        // console.log(error);
    }
}


// Update post
// exports.updatePost = async (req, res) => {
//     const { id } = req.params;
//     const { title, message, creator, selectedFile, tags } = req.body;
    
//     if (!mongoose.Types.ObjectId.isValid(id)) return res.status(404).send(`No post with id: ${id}`);

//     const updatedPost = { creator, title, message, tags, selectedFile, _id: id };

//     await PostMessage.findByIdAndUpdate(id, updatedPost, { new: true });

//     res.json(updatedPost);
// }

exports.updatePost = async (req,res) => {
    try {
        const post = await PostMessage.findById(req.params.id);

        if (!post) {
            return res.status(404).json({
              success: false,
              message: "Post not found",
            });
        }

        if (post.owner.toString() !== req.user._id.toString()) {
            return res.status(401).json({
              success: false,
              message: "Unauthorized",
            });
        }
        post.title = req.body.title;
        post.title = req.body.shortDescription;
        post.message = req.body.message;
        post.tags = req.body.tags;
        post.category = req.body.category;
        
        if(req.body.image){
        const myCloud = await cloudinary.v2.uploader.upload(req.body.image, {
            folder: "posts",
          });
        post.image.public_id = myCloud.public_id
        post.image.url = myCloud.secure_url
        }
        await post.save();
        res.status(200).json({
            success: true,
            post,
            message: "Post Updated",
        });

    }catch(error) {
        res.status(500).json({
            success: false,
            message: error.message,
          });
    }
}

// Delete post
exports.deletePost = async (req, res) => {
    const { id } = req.params;

    if (!mongoose.Types.ObjectId.isValid(id)) return res.status(404).send(`No post with id: ${id}`);

    await PostMessage.findByIdAndRemove(id);

    res.json({ message: "Post deleted successfully." });
}


// Like post
exports.likePost = async (req, res) => {
    const { id } = req.params;
    
    



    // if (!mongoose.Types.ObjectId.isValid(id)) return res.status(404).send(`No post with id: ${id}`);
    
    const post = await PostMessage.findById(id);

    if (!post) {
        return res.status(404).json({
          success: false,
          message: "Post not found",
        });
    };


    const updatedPost = await PostMessage.findByIdAndUpdate(id, { likeCount: post.likeCount + 1 }, { new: true });
    
    res.json(updatedPost);
}

exports.getPostsByTag = async (req, res) => {
    try{
        const { tag } = req.params;

        const regex = new RegExp(tag, 'i');
        const posts = await PostMessage.find({ category: regex });
        res.status(200).json(
            posts
        );

    }catch(error) {
        return res.status(404).json({
            message: error.message
        })
    }
}    

// Comment on Post
exports.commentOnPost = async (req, res) => {

    try{
        const post = await PostMessage.findById(req.params.id);

        if (!post) {
          return res.status(404).json({
            success: false,
            message: "Post not found",
          });
        };
        
        post.comments.push({
            user : req.body.name,
            comment : req.body.comment,
        });
        await post.save();
        return res.status(200).json({
            success: true,
            message: "Comment added",
          });

    }catch(error){
        res.status(500).json({
            success: false,
            message: error.message,
          });
    }
}

exports.savePost = async (req,res) => {
    try{
        const {id} = req.body;

        
        console.log(id);
        const user = await User.findById(req.user._id);

        // user.saved.forEach((item, index) => {
        //     // console.log(item.toString());
        //     // console.log(id);
        //     // console.log(id.toString());
        //     // console.log("post",index,"postId",item)
        //     if(item.toString() === id.toString()) {
        //         user.saved.splice(index,1)
                
                
        //         res.status(200).json({
        //             success: true,
        //             user,
        //             message: "Post Saved",
        //           });


        //     }
        // })

        if(user.saved.includes(id)) {
            const index = user.saved.indexOf(id);
        
        user.saved.splice(index,1);
        await user.save()
        return res.status(200).json({
            success: true,
            message: "Post Unsaved",
          });
        }else {
            user.saved.push(id); // worked
            await user.save();

            res.status(200).json({
                success: true,
                message: "Post Saved",
              });
        }
        

        // user.savepost.push(id);
        // user.savep.push(id);

    }catch(error) {
        res.status(409).json({ 
            success : false,
            message: error.message 
        });
    }
}

// Get Trending Post
exports.getTrendingPosts = async (req, res) => { 
    try {
        const posts = await PostMessage.find({isTrending : true});
                
        res.status(200).json(
            posts
        );
    } catch (error) {
        res.status(404).json({ message: error.message });
    }
}




