import "./TopStyle.css";
import { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import Post from "./Post/Post"
import { getPosts } from '../../actions/post';


const Posts = () => {
    const dispatch = useDispatch();
    useEffect(() => {
        dispatch(getPosts());
        } , [dispatch]);
    const {posts} = useSelector((state) => state.posts);

    return (
        <div className='parDiv'>
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
    );
}
export default Posts;