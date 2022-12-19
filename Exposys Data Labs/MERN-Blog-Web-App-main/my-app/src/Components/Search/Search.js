import { useState } from "react";
import { useDispatch } from "react-redux";
import { useNavigate } from "react-router-dom";
import { getPostsBySearch } from "../../actions/post";

import "./Search.css"

const Search = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    
    const searchPost = (e) => {
        e.preventDefault();
        dispatch(getPostsBySearch(search));
        navigate(`/search/posts?searchQuery=${search}`);
    };
    
    return (
        <div className="search-mainn">

            <div className="serachh-window">
                <div className="search-headingg">
                    <h3>SEARCH</h3>
                    <hr></hr>
                </div>
                
                <div className="search-input-tagss">
                <form>
                    <input
                        type="text"
                        className="search-input-fieldd"
                        placeholder="Search Here..."
                        name="search"
                        onChange={(e) => setSearch(e.target.value)}
                        value={search}
                    />
                    <button className="search-btnnn" onClick={searchPost}><i className="glyphicon glyphicon-search"></i></button>
                </form>
                </div>
            </div>
        </div>
    )
}

export default Search;