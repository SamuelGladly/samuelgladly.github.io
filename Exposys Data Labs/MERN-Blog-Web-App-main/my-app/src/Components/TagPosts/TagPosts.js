import "./TagPosts.css"

import { useSelector } from "react-redux";
import Post from "../Posts/Post/Post";

import AllUsers from "../AllUsers/AllUsers";
import SideTitles from "../SideTitles/SideTitles";
import Search from "../Search/Search"
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'


import {faTag} from '@fortawesome/free-solid-svg-icons'
import { useEffect } from "react";

const TagPosts = () => {
    useEffect(() => {
        window.scrollTo(0, 0)
        document.title ='Tag Blogs'
      }, [])

    const {posts} = useSelector((state) => state.tagPosts);
    console.log(posts);
    console.log(posts[0]?.category)

    return (
        <div>
             <div className="tagg-main">

                <div className="tagg-resultt">
                    <div className="tagg-res-left">
                    <h4><FontAwesomeIcon icon={faTag} className='tagg-iconn-one' />{posts[0]?.category}</h4>
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
                    </div>
                    <div className="tagg-res-righ">
                        <Search />
                        <AllUsers />
                        <SideTitles />
                    </div>
                </div>
            </div>

        </div>
    )
}

export default TagPosts;