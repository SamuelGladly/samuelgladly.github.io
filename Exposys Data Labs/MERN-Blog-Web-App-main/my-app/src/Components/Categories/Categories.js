
import { getPosts, getPostsBySearch, getPostsByTags } from "../../actions/post";
import { useNavigate } from "react-router-dom";
import { useDispatch, useSelector } from 'react-redux';

import "./catt.css"
import CategoryButton from "../CategoryButton/CategoryButton";

const Categories = () => {
    return(
        <>
        <div className="parcatt">
            <div className="parcatt11">
            <h3> CATEGORIES</h3>
            <hr></hr>
            </div>
            
            <div className="catt-name-btnn">
                
                <div className="category-btn-name">
                    <CategoryButton catt = "Health" />
                </div>
                <div className="category-btn-name">
                    <CategoryButton catt = "Sports" />
                </div>
                <div className="category-btn-name">
                    <CategoryButton catt = "Politics" />
                </div>
                <div className="category-btn-name">
                    <CategoryButton catt = "Programming" />
                </div>
                <div className="category-btn-name">
                    <CategoryButton catt = "Travel" />
                </div>
                <div className="category-btn-name">
                    <CategoryButton catt = "Technology" />
                </div>
                <div className="category-btn-name">
                    <CategoryButton catt = "Business" />
                </div>
                <div className="category-btn-name">
                    <CategoryButton catt = "Life" />
                </div>
                <div className="category-btn-name">
                    <CategoryButton catt = "Food" />
                </div>
                <div className="category-btn-name">
                    <CategoryButton catt = "Humanity" />
                </div>
                
            </div>
        </div>
        </>
    )
};
export default Categories;