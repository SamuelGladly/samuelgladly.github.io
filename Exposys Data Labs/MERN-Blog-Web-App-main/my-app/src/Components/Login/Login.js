import "./loginn.css"

import { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { useNavigate, Link } from "react-router-dom";

import { GoogleLogin } from "react-google-login";
import FacebookLogin from 'react-facebook-login';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import {faGoogle} from '@fortawesome/free-brands-svg-icons';

import Divider from '@mui/material/Divider';
import {TextField, Button} from "@material-ui/core";


import LoginnImgg  from "../../images/icons/android-icon-96x96-removebg-preview.png"
import { googleAuth, loginUser } from "../../actions/user";


const Login = () => {

    const GOOGLE_CLIENT_ID ="877839825734-gm5817fj03oamdkm6b9th73obcsngv7e.apps.googleusercontent.com";
    const GOOGLE_CLIENT_SECRET = "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";


    const dispatch = useDispatch();
    const navigate = useNavigate();

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');

    useEffect(() => {
        document.title = 'Login';
      });
      
    

    const loginHandler = (e) => {
        e.preventDefault();
        dispatch(loginUser(email, password, navigate));
        
    }

    // const onSuccess = (res) => {
    //     console.log("Login success! Current user: ", res);
    //     console.log(res?.tokenId);
    //     console.log("btween line");
    //     // console.log(res.credential)
    //     // dispatch(googleAuthe(res, navigate));
    // }

    const onSuccess = (ress) => {
        console.log(ress);
        dispatch(googleAuth(ress, navigate));
        
    }

    const onFailure = (res) => {
        console.log("Login Failed! res : ", res);
    }

    const responseFacebook = (ess) => {
        console.log(ess)
        // console.log(ess.accessToken);
        // console.log(ess.name);
        // console.log(ess.userID);
        const accessToken = ess.accessToken
        const userID = ess.userID
        // dispatch(facebookAuthe(accessToken,userID, navigate));
    }
    const customStyle = {
        fontSize : "20px",
        width: "350px",
        backgroundColor: 'white',
        border: 'none',
        borderRadius: '5px',
        boxShadow: "1px 1px 5px rgba(0, 0, 0, 0.3)",
        margin: '10px',
        padding: "14px 5px",
        fontWeight : 'bold'
    }

    return (
        
        <div className="login-main">

            <div className="login">

                <div className="login-window">
                    <div className="login-header">

                        {/* <img src={LoginnImgg} alt="login image" className="login-logoo" /> */}
                        <h1>TheBlogNotes </h1>
                    </div>

                    <div className="login-body1">
                        <div className="gAuth-login">
                            <GoogleLogin 
                                clientId={GOOGLE_CLIENT_ID}
                                buttonText="Continue with Google"
                                onSuccess={onSuccess}
                                onFailure={onFailure}
                                cookiePolicy={'single_host_origin'}
                                isSignedIn={true}
                                render={renderProps => (
                                    <button onClick={renderProps.onClick} style={customStyle}> <FontAwesomeIcon icon={faGoogle} className='googleIconn'/>    Continue with Google</button>
                                  )}
                              
                            /> 
                        </div>
                        <div className="FbAuth-login">
                                <FacebookLogin
                                    appId="3354011338166829"
                                    autoLoad={false}
                                    size="small"
                                    textButton="Continue with Facebook"
                                    cssClass="my-facebook-button-class"
                                    icon="fa-facebook"
                                    // fields="name,email,picture"
                                    // onClick={componentClicked}
                                    callback={responseFacebook} 
                                />
                        </div>
                    </div>
                    <div className="login-orr">
                    <Divider  style={{width : '100%'}}   >Or</Divider>
                    </div>

                    
                    <div className="login-body22">
                    
                        <form method="POST" onSubmit={loginHandler}>
                            <TextField
                                margin="normal"
                                name="email"
                                variant="outlined"
                                label="Email"
                                fullWidth
                                required
                                autoComplete="email"
                                autoFocus
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                InputProps={{ style: { fontSize: 17 } }}
                                InputLabelProps={{ style: { fontSize: 17 } }}

                            />
                            <TextField
                                id="outlined-password-input"
                                variant="outlined"
                                label="Password"
                                type="password"
                                margin="normal"
                                fullWidth
                                size='Normal'
                                required
                                autoComplete="current-password"
                                value = {password}
                                onChange={(e) => setPassword(e.target.value)}
                                InputProps={{ style: { fontSize: 17 } }}
                                InputLabelProps={{ style: { fontSize: 17 } }}
                            />
                            <Button
                                variant="contained"
                                color="primary"
                                size="large"
                                type="submit"
                                margin="normal"
                                style={{ lineHeight: "30px", fontSize:'20px', backgroundColor:'#feb98e',textTransform:'capitalize',margin:'10px 0px', color : 'black'  }}
                                fullWidth
                                >Login</Button>
                        </form>
                        
                       
                    </div>
                    <div className="login-footer">
                        <Link  to="/forgot/password">Forgot Password</Link>
                        <Link  to="/register">Create New Account</Link>
                    </div>
                </div>
            </div>
            
            
        </div>
    )
}

export default Login;



























