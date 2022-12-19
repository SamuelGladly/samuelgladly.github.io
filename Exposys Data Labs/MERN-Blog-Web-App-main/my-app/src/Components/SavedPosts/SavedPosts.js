import "./savedpostts.css";


import AllUsers from "../AllUsers/AllUsers";
import SideTitles from "../SideTitles/SideTitles";

import Search from "../Search/Search"


import { useEffect } from "react";

import { useDispatch, useSelector } from "react-redux";
import { getMySavedPosts } from "../../actions/user";
import Post from "../Posts/Post/Post";


const SavedPosts = () => {
    const dispatch = useDispatch()

    const {posts} = useSelector((state) => state.mysavedPosts);
    console.log(posts);

    useEffect(() => {
        dispatch(getMySavedPosts());
        window.scrollTo(0, 0)
    } , [dispatch]);

    return (
        <div>
            <div className="savedd-main">
                <div className="savedd-resultt">
                    <div className="savedd-res-left">
                        <h4>Saved Posts</h4>
                        <hr></hr>

                        
                            <h2>No Saved Post</h2>
                        
                            <div>
                                {(posts && posts.length > 0) && posts.map(post => {
                                    return (
                                        <>
                                            <Post post={post} key={post._id} />
                                        </>
                                    )
                                })}
                            </div>
                       


                    </div>
                    <div className="savedd-res-righ">
                        <Search />
                        <AllUsers />
                        <SideTitles />
                    </div>
                </div>
            </div>
        </div>
        
    )
}

export default SavedPosts;