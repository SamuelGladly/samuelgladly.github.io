const mongoose = require('mongoose');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const validator = require('validator');


const crypto = require('crypto');

const JWT_SECRET = "secret";

const userSchema = new mongoose.Schema({
    name : {
        type : String,
        
    },
    email : {
        type : String,
        required : [true, 'Email is required'],
        unique : true,
        validate : [validator.isEmail, 'Please provide a valid email'],
    },
    password : {
        type : String,
        required : [true, 'Password is required'],
        minlength : [6, 'Password must be at least 6 characters'],
    },
    image: {
        public_id: String,
        url: String,
    },
    bio : String,
    profession : String,  
    saved : [{
        type : mongoose.Schema.Types.ObjectId,
        ref : 'Post',
    }],
    // savepost :[Array],
    // savep :[String],
    posts : [{
        type : mongoose.Schema.Types.ObjectId,
        ref : 'Post',
    }],
    resetPasswordToken: String,
    resetPasswordExpire: Date,
});

userSchema.pre('save', async function(next){
    
    if(!this.isModified('password')){
        next(); // if password is not modified, then jump out of callback function immediately
    }
        this.password  = await bcrypt.hash(this.password, 12);
    });

userSchema.methods.matchPassword = async function(password){
    // // console.log(password); // password that user entered in the form to login
        // // console.log(this.password); // password that was stored in the database 
        return await bcrypt.compare(password, this.password)
    }
    
    // Get JWT TOKEN
    userSchema.methods.getJWTToken = function(){
        return jwt.sign({id: this._id },JWT_SECRET);
        
    };   

// this function will generate reset password token
// it will re encrypt it again with the same function  
// and store it in our database
userSchema.methods.getResetPasswordToken = function () {

    // it will just generate a token
    const resetToken = crypto.randomBytes(20).toString("hex");

    // console.log(resetToken); //a0e943bc131346710790b973c5b201d37a67f91b

    
    // now save this token to database 
    // in the variable name : resetPasswordToken
    // but before saving it, hash it again

    // WE HAVE HASHED THE TOKEN AND SAVED IT TO DATABASE
    this.resetPasswordToken = crypto.createHash("sha256").update(resetToken).digest("hex");

    // this will only be valid for 10 minute

    this.resetPasswordExpire = Date.now() + 10 * 60 * 1000;
  
    //We have returned the token generated from crypto moudle
    return resetToken;
};    

module.exports = mongoose.model('User', userSchema);























