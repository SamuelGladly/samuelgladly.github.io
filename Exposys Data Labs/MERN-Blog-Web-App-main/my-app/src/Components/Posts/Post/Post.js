import "./Poststyle.css";


import { useDispatch, useSelector } from 'react-redux';
import {useNavigate } from "react-router-dom";
import { getUserProfile } from "../../../actions/user";

import BookmarkIcon from '@mui/icons-material/Bookmark';
import BorderColorIcon from '@mui/icons-material/BorderColor';
import DeleteIcon from '@mui/icons-material/Delete';



import { deletePost, savePost } from "../../../actions/post";

import CategoryButton from "../../CategoryButton/CategoryButton";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome' 
import {faCalendarDays} from '@fortawesome/free-regular-svg-icons'

// import { , useSelector } from 'react-redux';

const Post = (props) => {

    const dispatch = useDispatch();
    const navigate = useNavigate();
    const {isAuthenticated} = useSelector((state) => state.user);

    const {userprofile} = useSelector((state) => state.userProfile);
    const { user } = useSelector((state) => state.user);
    
    const openPost = () => {
        navigate(`/posts/${props.post._id}`);
    }
    const openUser = () => {
        navigate(`/user/${props.post.owner}`);
    }

    const updateHandler = () => {
        navigate(`/update/post/${props.post._id}`)
    }
    const deleteHandler = () => {
        dispatch(deletePost(props.post._id));
    }
    const savePosthandler = () => {
        if(!isAuthenticated){
            alert("Login First");
        }else{
            dispatch(savePost(props.post._id));  
        }
    }

    return (
        <div className="postDiv">
            <div className="post-window">

                <div className="post-window-left" onClick={openPost}>
                    <img src={props.post.image.url} alt="post-img" className="post-imgg" />
                </div>
                <div className="post-window-right">

                    <div className="post-r-header">
                        <div className="post-r-header-left"> 
                            <CategoryButton catt = {props.post.category} /> 
                        </div>
                        <div className="post-r-header-right">
                            {/* <button onClick={openUser}>Author</button> */}
                        </div>
                    </div>

                    <div className="post-r-body" onClick={openPost}>
                        <h3>{props?.post?.title?.split(' ').splice(0,10).join(' ')}</h3>
                        <h6>{props.post.shortDescription.split(' ').splice(0,10).join(' ')}...</h6>
                        <p>{(props.post.tags) && props.post.tags.map((tag) =>` #${tag}`)}  </p> 
                    </div>
                    <div className="post-r-footer">
                        
                        <p> <FontAwesomeIcon icon={faCalendarDays} className='calender-deta'  />
                            {new Date(props.post.createdAt).toLocaleDateString('en-US',{day :'2-digit' })}
                            {new Date(props.post.createdAt).toLocaleDateString('en-US',{month:'short'})}
                            {new Date(props.post.createdAt).getFullYear()}
                        </p>
                        <div>
                        <BookmarkIcon fontSize="large" onClick={savePosthandler} className="save-bttnn" />
                        
                        {(user?._id === props.post.owner) && (
                            <>
                                <BorderColorIcon fontSize="large" onClick={updateHandler} className="save-bttnn" />
                                <DeleteIcon fontSize="large" onClick={deleteHandler} className="save-bttnn" />
                            </>    
                        )}
                        </div>
                    </div>
                     
                </div>
                
            </div>
            <hr></hr>
        </div>
    );
}
export default Post;