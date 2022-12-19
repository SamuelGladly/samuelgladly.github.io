const User = require('../models/userM');
const PostMessage = require('../models/postMessage.js');

const {sendEmail} = require("../middleware/sendEmail");

const crypto = require('crypto');
// Register

const cloudinary = require('cloudinary');

const {OAuth2Client} = require('google-auth-library');

const client = new OAuth2Client("877839825734-gm5817fj03oamdkm6b9th73obcsngv7e.apps.googleusercontent.com");



exports.register = async (req,res) => {

    // var r = req.body;
    // console.log(typeof(r)); // object

    try{
        // appling object destructuring
    const { name, email, password } = req.body;

    const emailLow = email.toLowerCase();

    if(!name || !email || !password){
        return res.status(400).json({
            status : 'error',
            message : 'Please fill all the fields'
        });
    }

    const existingUser = await User.findOne({email : emailLow});
    if(existingUser){
        return res.status(400).json({
            status : 'error',
            message : "User already exists",
        })
    }

    const user = await User.create({
        name,
        email:emailLow, 
        password,
    });
    const token = await user.getJWTToken();

    const options = {
      expires : new Date(Date.now() + 90 * 24 * 60 * 60 * 1000),
      httpOnly : true,
    }

    return res.status(201)
    .cookie("token", token,options)
    .json({
        success : 'true',
        user,
        token,
        message : 'Registration Successful'
    });

    }catch(err){
        res.status(500).json({
            status : "error",
            message :err.message, 
        });
    }
}


// Login user
exports.login = async (req,res) => {
    const {email, password} = req.body;
    // console.log(email);
    // console.log(password);
    const emailLower = email.toLowerCase();
    try {

        if(!email || !password){
            return res.status(400).json({
                success : false,
                message : 'Please fill all the fields',
            });
        }

        const user = await User.findOne({email:emailLower});
        if(!user){
            return res.status(400).json({
                success : false,
                message : 'Invalid credentials'
            });
        }

        const isMatch = await user.matchPassword(password);
        if(!isMatch){
            return res.status(400).json({
                success : false,
                message : 'Invalid credentials'
            });
        }

        const token = await user.getJWTToken();
        
        const options = {
            expires : new Date(Date.now() + 90 * 24 * 60 * 60 * 1000),
            httpOnly : true,
        }
        
        res.status(200)
        .cookie("token", token,options)
        .json({
            success : true,
            token,
            user,
            message : 'Logged in successfully',
        });
    } catch (error) {
        // console.log(error);
        res.status(500).son({
            success : false,
            message : 'Server Error'
        });
    }
}

// Logout user
exports.logout = async (req,res) => {
    try {
        res.clearCookie("token");
        res.status(200).json({
            success : true,
            message : 'Logged out successfully'
        });
    } catch (error) {
        res.status(500).json({
            success : false,
            message : 'Server Error'
        });
    }

}

// Get My Profile
exports.getProfile = async (req,res) => {
    try {
        const user = await User.findById(req.user._id).select('-password');
        res.status(200).json({
            success : true,
            user,
            // message : 'User Profile',
        });
    } catch (error) {
        res.status(500).json({
            success : false,
            message : error.message,
        });
    }
}
// Get my posts
exports.getMyPosts = async (req,res) => {
    try {
        const user = await User.findById(req.user._id).select('-password');
        const posts = await PostMessage.find({owner : user._id});
        res.status(200).json({
            success : true,
            posts,
            message : 'My posts ',
        });
    } catch (error) {
        res.status(500).json({
            success : false,
            message : 'Server Error'
        });
    }
}
// Get all users 
exports.getAllUsers = async (req,res) => {
    try {
        const users = await User.find({}).select('-password');
        res.status(200).json({
            success : true,
            users,
            message : 'All users',
        });
    } catch (error) {
        res.status(500).json({
            success : false,
            message : 'Server Error'
        });
    }
};

// Get a particular user profile using their id
exports.getUserProfile = async (req, res) => {
    try{
        console.log(req.params.id);
        const user = await User.findById(req.params.id);

        if (!user) {
            return res.status(404).json({
              success: false,
              message: "User not found",
            });
          }

        res.status(200).json({
        success: true,
        user,
        });  

    }catch(error){
        res.status(500).json({
            success : false,
            message : error.message,
        });
    }

}

exports.getUserPosts = async (req,res) => {
    try{
        const user = await User.findById(req.params.id);
        const posts = await PostMessage.find({owner : user._id});
        res.status(200).json({
            success : true,
            posts,
            message : 'Users post ',
        });

    }catch(error) {
        res.status(500).json({
            success: false,
            message: error.message,
          });
    }
}

// Update My Profile
exports.updateProfile = async (req, res) => {
    try {
    //   const user = await User.findById(req.user._id);
      const user = await User.findById(req.user._id); 
      const { name, email, bio, profession } = req.body;
  
    //   if (name) {
    //     user.name = name;
    //   }
    //   if (email) {
    //     user.email = email;
    //   }
        user.name = name;
        user.email = email;
        user.bio = bio;
        user.profession = profession;
        if(req.body.image) {
            const myCloud = await cloudinary.v2.uploader.upload(req.body.image, {
                folder: "users",
            });

            user.image.public_id = myCloud.public_id
            user.image.url = myCloud.secure_url
        }

    //   if (avatar) {
    //     await cloudinary.v2.uploader.destroy(user.avatar.public_id);
  
    //     const myCloud = await cloudinary.v2.uploader.upload(avatar, {
    //       folder: "avatars",
    //     });
    //     user.avatar.public_id = myCloud.public_id;
    //     user.avatar.url = myCloud.secure_url;
    //   }
  
      await user.save();
  
      res.status(200).json({
        success: true,
        user,
        message: "Profile Updated",
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        message: error.message,
      });
    }
  };


// Get my saved post
exports.getMySavedPost = async (req,res) => {
    try{
        const user = await User.findById(req.user._id);

        const posts = [];

        // console.log("Saved Length : ", user.saved.length); // 3

        // console.log(PostMessage(user.saved[0]));

        for (let i =0; i<user.saved.length;i++ ) {
            const post = await PostMessage.findById(user.saved[i])
            // console.log(post);
            posts.push(post)
        }
        res.status(200).json({
            success: true,
            posts,
        });
    }catch(error) {
        res.status(500).json({
            success : false,
            message : error.message,
        });
    }
}  

// Update Password
exports.updatePassword = async (req, res) => {
    try {
      const user = await User.findById(req.user._id);
    //   const user = await User.findById(req.user._id);
  
      const { oldPassword, newPassword } = req.body;
  
      if (!oldPassword || !newPassword) {
        return res.status(400).json({
          success: false,
          message: "Please provide old and new password",
        });
      }
  
      const isMatch = await user.matchPassword(oldPassword);
      if (!isMatch) {
        return res.status(400).json({
          success: false,
          message: "Incorrect Old password",
        });
      }
      user.password = newPassword;
      await user.save();
  
      res.status(200).json({
        success: true,
        message: "Password Updated",
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        message: error.message,
      });
    }
  };

  // Forgot Password 

  exports.forgotPassword = async (req, res) => {
    try {

      const {email} = req.body;
      const emailLow = email.toLowerCase();
      const user = await User.findOne({ email: emailLow });
      console.log(emailLow);
      if (!user) {
        return res.status(404).json({
          success: false,
          message: "User not found",
        });
      }
  
      const resetPasswordToken = await user.getResetPasswordToken();
  
      await user.save();
      
  
      // now create link 
      console.log(req.protocol);
      console.log("Host ", req.get("host"))
      // console.log(req);

      // const resetUrl = `${req.protocol}://${req.get("host")}/user/password/reset/${resetPasswordToken}`;
      console.log('Base',process.env.BASE_URL);
      const resetUrl = `${process.env.BASE_URL}/password/reset/${resetPasswordToken}`;


      const message = `Reset Your Password by clicking on the link below: \n\n ${resetUrl}`;
  
      try {
        await sendEmail({
          email: user.email,
          subject: "Reset Password",
          message,
        });
  
        res.status(200).json({
          success: true,
          message: `Email sent to ${user.email}`,
        });
      } catch (error) {
        // Since the email is not sent, then we need to undo the 
        // changes we did in the database  
        user.resetPasswordToken = undefined;
        user.resetPasswordExpire = undefined;
        await user.save();
  
        res.status(500).json({
          success: false,
          message: error.message,
        });
      }



    } catch (error) {
      res.status(500).json({
        success: false,
        message: error.message,
      });
    }
  };

  // In this function we will compare the token which we have 
  // got from email (after the user have clicked on the link),
  // and the token we have saved to our database (which we generated
  // for the user to reset his password)

  // And if the token doesn't mactch, then it either invalid token
  // or expire token

  
  exports.resetPassword = async (req, res) => {
    try {
      const resetPasswordToken = crypto
        .createHash("sha256")
        .update(req.params.token)
        .digest("hex");
  
      const user = await User.findOne({
        resetPasswordToken,
        resetPasswordExpire: { $gt: Date.now() },
      });
  
      if (!user) {
        return res.status(401).json({
          success: false,
          message: "Token is invalid or has expired",
        });
      }
  
      user.password = req.body.password;
  
      user.resetPasswordToken = undefined;
      user.resetPasswordExpire = undefined;
      await user.save();
  
      res.status(200).json({
        success: true,
        message: "Password Updated",
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        message: error.message,
      });
    }
  };


  exports.googleAuth = async (req,res) => {
    try {
      const {ress} = req.body;
  
      // console.log(ress.tokenId);
  
  
  
      const ticket = await client.verifyIdToken({
        idToken: ress.tokenId,
        audience: "877839825734-gm5817fj03oamdkm6b9th73obcsngv7e.apps.googleusercontent.com",
      });
      const googleUserData = ticket.getPayload();
  
      // console.log(googleUserData);
      // console.log(googleUserData.name);
      // console.log(googleUserData.email);
  
      const user = await User.findOne({email:googleUserData.email })
  
      if(user){
        // console.log(user);
        const token = await user.getJWTToken();
  
        const options = {
          expires : new Date(Date.now() + 90 * 24 * 60 * 60 * 1000),
          httpOnly : true,
        }
        return res.status(201)
        .cookie("token",token,options)
        .json({
            success : true,
            user,
            token,
            message : "User logged in successfully"
        });
      }else {
        // console.log("user not there");
        let passwordcustom = googleUserData.email + "JUST"
        const user = await User.create({
          name : googleUserData.name,
          email : googleUserData.email,
          password : passwordcustom,
        });
        const token = await user.getJWTToken();
        const options = {
          expires : new Date(Date.now() + 90 * 24 * 60 * 60 * 1000),
          httpOnly : true,
        }
        return res.status(201)
        .cookie("token",token,options)
        .json({
          status : 'success',
          user,
          token,
          message : 'Registration Successful'
        });
      }
  
      res.status(200).json({
        message : "Data received"
      })
    }catch(error){
      res.status(500).json({
        success : false,
        message : error.message,
      });
    }
  };
  
  
  
  
  
  
























































































