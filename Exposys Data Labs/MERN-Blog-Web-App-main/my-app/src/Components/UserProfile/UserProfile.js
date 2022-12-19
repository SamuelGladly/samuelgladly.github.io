import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { getUserPosts, getUserProfile } from "../../actions/user";
import { useParams } from "react-router-dom";
import Post from "../Posts/Post/Post";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import {faFacebook, faLinkedin, faYoutube, faInstagram, faTwitter} from '@fortawesome/free-brands-svg-icons';

import "./userprofilee.css"
import AllUsers from "../AllUsers/AllUsers";
import SideTitles from "../SideTitles/SideTitles";
import Footer from "../Footer/Footer";
const UserProfile = () => {

    const dispatch = useDispatch();

    const { id } = useParams();

    useEffect(() => {
        dispatch(getUserProfile(id));
        dispatch(getUserPosts(id));
        window.scrollTo(0, 0)
    } , [dispatch, id]);

    const {userprofile} = useSelector((state) => state.userProfile);
    const {posts} = useSelector((state) => state.userPosts);
    
    // console.log(posts);
    if(userprofile?.image?.url){
        var imgloc = userprofile?.image?.url
    }else {
        imgloc = "https://res.cloudinary.com/dayypkgas/image/upload/v1662312587/users/user-removebg-preview_tsnhjg.png"
    }

    return (
        <>
        <div className="user-profile-main">

            <div className="user-prof">
                <div className="user-prof-left">
                    <div className="user-posts">
                        <h4>Posts by {userprofile?.name}</h4>
                        <hr></hr>
                            <div>
                            {(posts && posts.length > 0) && posts.map(post => {
                                return (
                                <>
                                    <Post post={post} key={post._id} />
                                </>
                                )
                            })}
                            </div>
                        <div className="other-sections-u">
                        <AllUsers />
                        <SideTitles />
                    </div>
                    </div>
                </div>
                <div className="user-prof-right">
                    <div className="user-prof-details">
                        <h3>ABOUT AUTHOR</h3>
                        <hr></hr>
                        <img src={imgloc} alt="user-img" className="user-pro-img" />
                        <p>{userprofile?.name}</p>
                        <p>{userprofile?.profession}</p>
                        <p>{userprofile?.bio}</p> 
                        <div className="my-social-prof" >
                            
                            <FontAwesomeIcon icon={faFacebook} className='my-social-icons'/>
                            <FontAwesomeIcon icon={faLinkedin} className='my-social-icons'/>
                            <FontAwesomeIcon icon={faTwitter} className='my-social-icons'/>
                            <FontAwesomeIcon icon={faInstagram} className='my-social-icons'/>
                            <FontAwesomeIcon icon={faYoutube} className='my-social-icons'/>
                        </div> 
                    </div>
                    <div className="other-sections">
                        <AllUsers />
                        <SideTitles />
                    </div>
                </div>
            </div>
        </div>
        <Footer />
        </>
    );
}

export default UserProfile;





















