// import mongoose from 'mongoose';
const mongoose = require('mongoose');

const postSchema = new mongoose.Schema({
    title: String,
    message: String,
    tags: [String], // array of strings
    category: String, // image url
    shortDescription : {
      type:String,
      maxlength : [50, 'Short Description must be 50 Charcters only'],
    },
    image: {
      public_id: String,
      url: String,
    },
    owner : {
        type : mongoose.Schema.Types.ObjectId,
        ref : 'User',
    },
    likeCount: {
        type: Number,
        default: 0,
    },
    createdAt: {
        type: Date,
        default: new Date(),
    },
    isTrending: {
      type: Boolean,
      default: false
    },
    mainOne : {
      type : Boolean,
      default :false
    },
    mainTwos : {
      type : Boolean,
      default :false
    },

    comments: [
        {
          user: {
            type: String,
            required :true
          },
          comment: {
            type: String,
            required: true,
          },
        },
      ],

});

// var PostMessage = mongoose.model('PostMessage', postSchema);

// export default PostMessage;
module.exports = mongoose.model('PostMessage', postSchema);