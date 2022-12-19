import "./profilee.css"
import { useEffect } from "react";

import Divider from '@mui/material/Divider';

import { Link, useNavigate } from "react-router-dom";

import { useDispatch, useSelector } from "react-redux";
import { getMyPosts, loadUser } from "../../actions/user";
import Post from "../Posts/Post/Post";
import AllUsers from "../AllUsers/AllUsers";
import SideTitles from "../SideTitles/SideTitles";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'

import {faFacebook, faLinkedin, faYoutube, faInstagram, faTwitter} from '@fortawesome/free-brands-svg-icons';

import {faArrowAltCircleRight} from '@fortawesome/free-regular-svg-icons'
const Profile = (props) => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    // useEffect(() => {
    //     dispatch(loadUser());
    //     } , [dispatch]);
    useEffect(() => {
        dispatch(getMyPosts());
        document.title = 'Profile';
        window.scrollTo(0, 0)
    } , [dispatch]);

    const { user, loading: userLoading } = useSelector((state) => state.user);
    const {posts} = useSelector((state) => state.myPosts); 
    // console.log(posts);
    // console.log(user);
    // console.log(props.user.user.user.name)

    const fbb = ()  => {
        navigate('/')
    }
    if(user.image?.url){
        var imgloc = user.image?.url
        // console.log( "1", imgloc1)
    }else {
       var imgloc = "https://res.cloudinary.com/dayypkgas/image/upload/v1662312587/users/user-removebg-preview_tsnhjg.png"
    //    console.log("2",imgloc2)
    }    
    return(
        <div className="my-profile-main">
            <div className="profile">

                <div className="user-profm">
                <div className="user-prof-left">
                    <div className="user-posts">
                        <h4>Posts by {user?.name}</h4>
                        <hr></hr>
                        <div className="user-pstdet">
                        {(posts && posts.length > 0) && posts.map(post => {
                            return (
                                <>
                            <Post post={post} key={post._id} />
                            </>
                            )
                        })}
                        </div>
                        <div className="my-prof-alluser-post">
                        <AllUsers />
                        <SideTitles />
                    </div>
                    </div>
                </div>
                <div className="user-prof-right">
                    <div className="user-prof-details">
                        <h3>ABOUT ME </h3>
                        <hr></hr>   
                        <img src={imgloc} alt="user-img" className="profile-imgg-my" />
                        <p className="my-name-pp"> {user?.name}</p>
                        <p>{user?.profession}</p>
                        <p>{user?.bio}</p>

                        <div className="my-social-prof" >
                            <FontAwesomeIcon icon={faFacebook} className='my-social-icons' onClick={fbb}/>
                            <FontAwesomeIcon icon={faLinkedin} className='my-social-icons'/>
                            <FontAwesomeIcon icon={faTwitter} className='my-social-icons'/>
                            <FontAwesomeIcon icon={faInstagram} className='my-social-icons'/>
                            <FontAwesomeIcon icon={faYoutube} className='my-social-icons'/>
                        </div> 
                        <hr></hr>    
                         <div className="update-my-prof-divv">  
                            <Link className="update-proff-my" to="/profile/update">Update Profile<FontAwesomeIcon icon={faArrowAltCircleRight} className='my-social-icon' /></Link>
                            <Link className="update-proff-my" to="/saved/post">Saved Post</Link>
                        </div> 
                        
                    </div>
                    <div className="my-prof-alluser">
                        <AllUsers />
                       <SideTitles />
                    </div>
                </div>

            </div>
            


            {/* <p>My name is {user.user.name}</p> */}
            </div>
        </div>
    )
};
export default Profile;