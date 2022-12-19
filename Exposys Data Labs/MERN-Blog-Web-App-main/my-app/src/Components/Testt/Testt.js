import "./testtt.css"
import { useNavigate, Link } from "react-router-dom";
import { useDispatch, useSelector } from "react-redux";
import { logoutUser } from "../../actions/user";

import { GoogleLogout } from "react-google-login";


const Testt = () => {
    const dispatch = useDispatch();

    const GOOGLE_CLIENT_ID ="877839825734-gm5817fj03oamdkm6b9th73obcsngv7e.apps.googleusercontent.com";

    const { isAuthenticated } = useSelector((state) => state.user);
    // const isAuthenticated = false;

    const logout = () => {
        dispatch(logoutUser());
    }

    const customStyle = {

        backgroundColor: 'white',
        border: 'none',
        fontSize:'18px',
        fontWeight: '900',
        fontFamily: 'Montserrat',
        marginTop : '10px',
        color : '#ffc4a0'

        

    }

    const onSucce =() => {
        // console.log("Logggg out Successfully");
        dispatch(logoutUser());
    }

    return (
        <div>
            <nav class="navbar navbar-default navbar-fixed-top">
                <div class="container">
                
                    <div class="navbar-header">
                    
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>                        
                    </button>
                    
                    <Link className="navbar-brand" to="/">TheBlogNotes</Link>
                    </div>

                    <div class="collapse navbar-collapse" id="myNavbar">
                    <ul class="nav navbar-nav navbar-right">
                        <li><Link to="/" >Home</Link></li>
                        <li><Link to="/form">Write</Link></li>
                        {isAuthenticated ?(
                            <> 
                                <li><Link to="/profile" >Profile</Link></li>
                                {/* <button className="nav-link" onClick={logout}>LOGOUT</button> */}

                                <GoogleLogout 
                                    clientId={GOOGLE_CLIENT_ID}
                                    buttonText={"Logout"}
                                    onLogoutSuccess={onSucce}
                                    onClick={logout}
                                    render={renderProps => (
                                        <button onClick={renderProps.onClick} style={customStyle}>  Logout</button>
                                    )}
                                />
                            </> 
                        ):(
                            <>
                                <li><Link to="/login" className="btn11" >Login</Link></li>
                                <li><Link to="/register" className="btn12" >Register</Link></li>
                            </>
                        )}
                    </ul>
                    </div>
                </div>
                </nav>
        </div>
    )
}

export default Testt;