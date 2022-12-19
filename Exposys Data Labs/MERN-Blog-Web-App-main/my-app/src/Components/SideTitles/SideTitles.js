
import "./sidetitle.css"

import { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { getPosts } from '../../actions/post';

import SideTitle from './SideTitle/SideTitle';

const SideTitles = () => {

    const dispatch = useDispatch();
        useEffect(() => {
            dispatch(getPosts());
          } , [dispatch]);
    const {posts} = useSelector((state) => state.posts);
       

    return (
        <div className='parr'>
        <h2> LATEST BLOGS </h2>
        
            <div>
                {(posts && posts.length > 0) && posts.slice(0, 5).map(post => {
                    return (
                    <>
                        <SideTitle post={post} key={post._id}/>
                    </>
                    )
                })}
            </div>
        </div>
    );
}
export default SideTitles;