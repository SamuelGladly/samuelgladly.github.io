import "./SearchPosts.css"

import { useSelector } from "react-redux";
import Post from "../Posts/Post/Post";
import AllUsers from "../AllUsers/AllUsers";
import SideTitles from "../SideTitles/SideTitles";

import Search from "../Search/Search"
import { useEffect } from "react";

const SearchPosts = () => {

    const {posts} = useSelector((state) => state.searchPosts);
    console.log(posts);

    useEffect(() => {
        document.title = 'Search Results';
        window.scrollTo(0, 0)
    });
    


    return (
        <div>


            <div className="search-main">
                <div className="search-resultt">
                    <div className="searh-res-left">
                        <h4>Results</h4>
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
                    <div className="search-res-righ">
                        <Search />
                        <AllUsers />
                        <SideTitles />
                    </div>
                </div>
            </div>
        </div>
        // <div>{posts ? (
        //     <>
        //     <p></p>
        //         <p>Results</p>
        //         <div>
        //             {(posts && posts.length > 0) && posts.slice(0, 9).map(post => {
        //                 return (
        //                     <>
        //                         <Post post={post} key={post._id} />
        //                     </>
        //                 )
        //             })}
        //         </div>
        //     </>

        // ) : (
        //     <div>
        //         <h1>No Post Found</h1>
        //         <h1>No Post Found</h1>
        //         <h1>No Post Found</h1>
        //         <h1>No Post Found</h1>
        //     </div>
            
            
        // )}
            

         

        // </div>
    )
}

export default SearchPosts;