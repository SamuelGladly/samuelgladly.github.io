const jwt = require('jsonwebtoken');
const User = require('../models/userM');

const JWT_SECRET = "secret";

exports.Authenticate = async (req, res, next) => {

    try{
    
    const {token} = req.cookies;
    // console.log(req.cookies); // { token :'fjaljfaljeliajflffjlajfleajflkajflajflajflaijfeliajfealijfalfj...'}
    // console.log(token); // shows the token
    // if there is no token, then the user is not logged in
    if(!token) {
        return res.status(401).json({
            message : "Please login first"
        });   
    }
    // res.send(token);

    
    // Now if there is an token i.e. the user is loggedin 
    // then
    // Now we will decode the token and get the "id" from that
    const decoded = await jwt.verify(token,JWT_SECRET);

    // console.log(decoded) //{ id: '6249b8e1c1119f4c807e4cb1', iat: 1649018743, exp: 1649450743 }
    // console.log(decoded.id) //6249b8e1c1119f4c807e4cb1

    // Now we will find the user with the id from the decoded token
    // now we have saved all the user's data into "req.user"
    req.user = await User.findById(decoded.id);

    // console.log(req.user) // it will show the user's data

    next();

    }catch(err){
       res.status(500).json({
           message : err.message,
       })

    }
};