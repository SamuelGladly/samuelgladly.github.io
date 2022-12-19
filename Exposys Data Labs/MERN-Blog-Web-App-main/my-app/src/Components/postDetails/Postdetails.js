import "./postdetailss.css";
import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { addComment, getPostDetails, getPostsBySearch } from "../../actions/post";

import {TextField, Button} from "@material-ui/core";


import { useNavigate } from "react-router-dom";

import { useDispatch, useSelector } from "react-redux";
import Post from "../Posts/Post/Post";
import { getUserProfile } from "../../actions/user";
import SideTitles from "../SideTitles/SideTitles";
import Categories from "../Categories/Categories";
import Search from "../Search/Search";


import { FontAwesomeIcon } from '@fortawesome/react-fontawesome' 
import {faCalendarDays} from '@fortawesome/free-regular-svg-icons'

const PostDetails = () => {

    const [name, setName] = useState('');
    const [comment, setComment] = useState('')

    const navigate = useNavigate();

    const dispatch = useDispatch();
    const { id } = useParams();
    const openUser = (id) => {
        navigate(`/user/${posts.posts.owner}`);
    }
    useEffect(() => {

        dispatch(getPostDetails(id)); 
        window.scrollTo(0, 0)
        document.title = 'Blog Details';

      } , [dispatch, id]);


      const posts = useSelector((state) => state.post);
      const pos =  useSelector((state) => state.searchPosts);
      const {userprofile} = useSelector((state) => state.userProfile);

    const commentHandler = (e) => {
        e.preventDefault();
        // console.log(name);
        // console.log(comment);
        dispatch(addComment(name, comment,id))
    } 
    useEffect(()=> {
        if(posts){
            let r = posts.posts?.category;    
            dispatch(getPostsBySearch(r));
            dispatch(getUserProfile(posts.posts?.owner));
        }
    },[posts]);

    if(userprofile?.image?.url){
        var imgloc = userprofile?.image?.url
    }else {
       var imgloc = "https://res.cloudinary.com/dayypkgas/image/upload/v1662312587/users/user-removebg-preview_tsnhjg.png"
    
    }   
    


    return (
        <>
        <div className="postdetails-main">

            <div className="profdet-main">
                <div className="profdet-main-left">

                    <div className="post-author-detailss">
                        <div className="post-author-prof-imagg" onClick={openUser}  >
                            <img src={imgloc} alt="user-img" className="post-auth-imgr" />
                        </div>
                        <div className="post-auth-detailss">
                           <h5 onClick={openUser} >{userprofile?.name}</h5>
                            <p> <FontAwesomeIcon icon={faCalendarDays} className='calender-deta'  />   
                                {new Date(posts.posts.createdAt).toLocaleDateString('en-US',{day :'2-digit' })}
                                {new Date(posts.posts.createdAt).toLocaleDateString('en-US',{month:'short'})}
                                {new Date(posts.posts.createdAt).getFullYear()}
                            </p>
                            
                        </div> 
                    </div>

                    <hr></hr>

                    <div className="post-header-detailss">
                        <h1>{posts.posts.title}</h1>
                        <p>{posts.posts.shortDescription}</p>
                    </div>

                    <div className="post-details-imgg">
                        <img src={posts.posts.image?.url} alt="image" className="post-det-img" />
                        <p>{(posts.posts.tags) && posts.posts.tags.map((tag) =>` #${tag}`)}  </p>
                        
                    </div>
                    <div className="post-body-messagee">
                        <p className="post-det-msgg">{posts.posts.message}</p>
                    </div>

                    <div className="profdet-footer">
                        
                        <div className="comment-form">
                            <div className="comment-form-window">
                                <h3> Add a Comment</h3>
                                <form onSubmit={commentHandler}>
                                <TextField
                                    name="name"
                                    variant="outlined"
                                    label="Name"
                                    fullWidth
                                    required
                                    margin="normal"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    InputProps={{ style: { fontSize: 15 } }}
                                    InputLabelProps={{ style: { fontSize: 13 } }}
                                />

                                <TextField
                                    name="comment"
                                    variant="outlined"
                                    label="Enter Comment"
                                    fullWidth
                                    required
                                    margin="normal"
                                    value={comment}
                                    multiline
                                    rows={3}
                                    onChange={(e) => setComment(e.target.value)}
                                    InputProps={{ style: { fontSize: 13 } }}
                                    InputLabelProps={{ style: { fontSize: 13 } }}
                                />
                                <Button
                                    variant="contained"
                                    color="primary"
                                    size="large"
                                    margin='normal'
                                    style={{ lineHeight: "20px", fontSize:'13px', backgroundColor:'#1FAAE1',textTransform:'capitalize',margin:'10px 0px'  }}
                                    type="submit"
                                    fullWidth
                                    >
                                    Submit
                                </Button>
                                </form>
                            </div>
                        </div>
                        <div className="posted-comments">
                            <h3>Comments</h3>
                            <div className="users-commentss">
                                {posts.posts.comments?.map(post =>{
                                return (
                                            <>
                                            <p>{post.user } : {post.comment}</p>
                                            
                                            </>
                                        )
                                })}
                                </div>
                        </div>
                    </div>

                </div>

                <div className="profdet-main-right">
                    <Search />
                    <Categories />
                    <SideTitles />
                </div>
            </div>
            <hr></hr> 


            <div className="suggestions">
                <div className="suggest-head-ing">
                <h1>Related Posts</h1>
                </div>
                <div className="home-trend">  
                <div className="leeeef">
                    {(pos.posts && pos.posts.length > 0) && pos.posts.slice(0, 3).map(post => {
                        return (
                            <>
                            <Post post={post} key={post._id} />
                            </>
                        )
                    })}
                </div>
                <div className="rigg">
                    {(pos.posts && pos.posts.length > 0) && pos.posts.slice(3, 6).map(post => {
                        return (
                            <>
                            <Post post={post} key={post._id} />
                            </>
                        )
                    })}
                </div>
            </div>

                {/* <div>
                    {(pos.posts && pos.posts.length > 0) && pos.posts.map(post => {
                        return (
                        <>
                            <Post post={post} key={post._id} />
                        </>
                        )
                    })}
                </div> */}
            </div>

            <div className="other-sectionss">
                    <Search />
                    <Categories />
                    <SideTitles />
            </div>
        </div>
        </>
    )

    }
    export default PostDetails;
