import "./homeStyle.css";
import { useEffect } from "react";
import { useDispatch, useSelector } from 'react-redux';
import { getPosts, getTrendingPosts } from "../../actions/post";
import Posts from "../Posts/Posts";
import SideTitles from "../SideTitles/SideTitles";
import AllUsers from "../AllUsers/AllUsers";
import Categories from "../Categories/Categories";
import Post from "../Posts/Post/Post";
import { useNavigate } from "react-router-dom";
import Categorysec from "../Categorysec/Categorysec";
import Trendtop from "../Trendtop/Trendtop";
import Search from "../Search/Search";


const Home = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    useEffect(() => {
        dispatch(getPosts());
        dispatch(getTrendingPosts());
        document.title = 'The Blog Notes';
        window.scrollTo(0, 0)
    } , [dispatch]);

    const newcall = () => {
        navigate(`/posts/${posts[9]._id}`)
    }
    const {posts}= useSelector((state) => state.trendPost);
    // console.log(posts.length);
    

    const myStyle={
        backgroundImage:`url(${JSON.stringify(posts[9]?.image?.url)})`,
                fontSize:'30px',
                backgroundSize: 'cover',
                backgroundRepeat: 'no-repeat',
                backgroundPosition : 'center',
                color:'white',
                
                };

    return (
        <div className="home">
            <div className="outter-one-mainn">
                <div class="flex-container">
                    <div class="flex-item-left">
                        <Categorysec />
                    </div>
                    
                    <div class="flex-item-right"  onClick={newcall}>
                        <div className="bg-imgg-postt12" style={myStyle}>
                            <p>{posts[9]?.title}</p>
                        </div>
                    </div>
                        <div class="flex-item-right2">
                                <div>
                                    {(posts && posts.length > 0) && posts.slice(6,9).map(post => {
                                        return (
                                            <>
                                                <Trendtop post={post} key={post._id} />
                                            </>
                                        )
                                    })}
                                </div>
                        </div>
                    </div>
                </div>

            <div className="trend-blog-posts1">
                <h2>TRENDING POSTS</h2>
                <hr></hr>
            </div>

            <div className="home-trend">  
                <div className="leeeef">
                    {(posts && posts.length > 0) && posts.slice(0, 3).map(post => {
                        return (
                            <>
                            {/* <Post post={post} key={post._id} /> */}
                            <Trendtop post={post} key={post._id} />
                            </>
                        )
                    })}
                </div>
                <div className="rigg">
                    {(posts && posts.length > 0) && posts.slice(3, 6).map(post => {
                        return (
                            <>
                            {/* <Post post={post} key={post._id} /> */}
                            <Trendtop post={post} key={post._id} />
                            </>
                        )
                    })}
                </div>
            </div>

            <div className="trend-blog-posts1">
                <h2>POSTS</h2>
            <hr></hr>
            </div>


            <div className="home-main">
                <div className="home-left-side">
                    <Posts />
                </div>
                <div className="home-right-side">
                    <Search />
                    <Categories />
                    <AllUsers />
                    <SideTitles />
                </div>
            </div>
        </div>
    );
    }
export default Home;
















