import { useDispatch } from "react-redux";
import { useNavigate } from "react-router-dom";
import { getPostsByTags } from "../../actions/post";

import "./cattbutt.css"
const CategoryButton = (props) => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    
    var cattt = props.catt;

    const catPost = () => {
        dispatch(getPostsByTags(cattt));
        navigate(`/tag/posts`);
    }
    return (
        <div className="cat-button">
            <button onClick={catPost} className="catego-btnn">{cattt}</button>  
        </div>
    )
};

export default CategoryButton;