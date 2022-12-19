import "./registerr.css"
import { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { useNavigate,Link } from "react-router-dom";
import { googleAuth, registerUser } from "../../actions/user";

import Divider from '@mui/material/Divider';
import {TextField, Button} from "@material-ui/core";
import RegisterImgg  from "../../images/icons/android-icon-96x96-removebg-preview.png"


import { GoogleLogin } from "react-google-login";
import FacebookLogin from 'react-facebook-login';
import { GoogleLogout } from "react-google-login";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import {faGoogle} from '@fortawesome/free-brands-svg-icons';


const Register = () => {

    const GOOGLE_CLIENT_ID ="877839825734-gm5817fj03oamdkm6b9th73obcsngv7e.apps.googleusercontent.com";
    const GOOGLE_CLIENT_SECRET = "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";

    const dispatch = useDispatch();
    const navigate = useNavigate();


    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');

    const regHandler = (e) => {
        e.preventDefault();
        // console.log(name, email, password);
        dispatch(registerUser(name, email, password, navigate));


    }
    const googleSuccess = async (ress) => {
        console.log(ress)
        // console.log("Login success! Current user: ", res);
        // console.log(res?.tokenId);
        // console.log("btween line");
        // console.log(res.credential)
        // dispatch(googleAuthe(res, navigate));
        dispatch(googleAuth(ress, navigate));
        
    }
    const googleError = (res) =>{
            // console.log("Google Sign In was unsuccessful. Try again later");
            // console.log(error)
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
    useEffect(() => {
        document.title = 'Register';
    });
    

    return (
        <div className="register-main">
            <div className="register">
            <div className="register-window">
                <div className="register-main-header">
                    {/* <img src={RegisterImgg} alt="login image" className="regg-logoo" /> */}
                    <h1>TheBlogNotes </h1>
                </div>
                <div className="register-main-body">
                    <div className="register-body11">
                    <div className="gAuth-login">
                            <GoogleLogin 
                                clientId={GOOGLE_CLIENT_ID}
                                buttonText="Continue with Google"
                                onSuccess={googleSuccess}
                                onFailure={googleError}
                                cookiePolicy={'single_host_origin'}
                                isSignedIn={true}
                                render={renderProps => (
                                    <button onClick={renderProps.onClick} style={customStyle}><FontAwesomeIcon icon={faGoogle} className='googleIconn' />    Continue with Google</button>
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

                    <div className="register-orr">
                        <Divider  orientation="vertical"style={{height : '100%'}} className='or-divider' >Or</Divider>
                    </div>

                    <div className="register-body22">
                        <form method="POST" onSubmit={regHandler}>
                            <TextField
                                    margin="normal"
                                    name="name"
                                    variant="outlined"
                                    label="Name"
                                    fullWidth
                                    required
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    InputProps={{ style: { fontSize: 17 } }}
                                    InputLabelProps={{ style: { fontSize: 17 } }}
                            />
                            <TextField
                                    margin="normal"
                                    name="name"
                                    variant="outlined"
                                    label="Email"
                                    fullWidth
                                    required
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    InputProps={{ style: { fontSize: 17 } }}
                                    InputLabelProps={{ style: { fontSize: 17 } }}
                            />
                            <TextField
                                    margin="normal"
                                    name="name"
                                    variant="outlined"
                                    label="Password"
                                    type="password"
                                    fullWidth
                                    required
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    InputProps={{ style: { fontSize: 17 } }}
                                    InputLabelProps={{ style: { fontSize: 17 } }}
                            />
                            <Button
                                variant="contained"
                                color="primary"
                                size="large"
                                type="submit"
                                margin="nomal"
                                style={{ lineHeight: "30px", fontSize:'20px', backgroundColor:'#feb98e',textTransform:'capitalize',margin:'10px 0px', color:'black'  }}
                                fullWidth
                                >
                                SignUp
                            </Button>
                        </form>
                        <div className="regg-footer">
                            <span className="already-user">Already a User ? </span><Link  to="/login">Login</Link>  
                        </div>
                    </div> 

                </div>
            </div> 
            </div>
        </div>
    )
}

export default Register;






