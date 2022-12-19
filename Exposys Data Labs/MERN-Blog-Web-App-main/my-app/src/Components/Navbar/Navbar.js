
import { useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useNavigate, Link } from "react-router-dom";
import "./Navstyle.css";
import { logoutUser } from "../../actions/user";
import { getPostsBySearch } from "../../actions/post";

import SearchIcon from '@mui/icons-material/Search';
const Navbar = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const { isAuthenticated } = useSelector((state) => state.user);
    const logout = () => {
        dispatch(logoutUser());
    }
    const searchPost = (e) => {
        e.preventDefault();
        dispatch(getPostsBySearch(search));
        // navigate(`/posts/search?searchQuery=${search}`);
        navigate(`/search/posts?searchQuery=${search}`);
    };
    return (
        <div className="header">
            <div className="header-left">
                {/* <img src={just} alt="img" /> */}
                <h1> <Link className="nav-link" to="/">LOGO</Link></h1>
            </div>
            <form>
                <input
                    type="text"
                    placeholder="Search"
                    name="search"
                    onChange={(e) => setSearch(e.target.value)}
                    value={search}
                />
                <button className="search-btnn" onClick={searchPost}><i className="glyphicon glyphicon-search"></i></button>
            </form>
            <div className="header-center">
                <ul className="list-group">
                    <li className="list-group-item">
                        <Link className="nav-link" to="/">HOME</Link>
                    </li>
                    <li className="list-group-item">
                        <Link className="nav-link" to="/form">Write</Link>
                    </li>
                </ul>
            </div>
            <div className="header-right">
                <ul className="list-group">
                    {isAuthenticated ? (
                        <>
                            <li className="list-group-item">
                                <Link className="nav-link" to="/profile">PROFILE</Link>
                            </li>
                            <li className="list-group-item">
                                <button className="nav-link" onClick={logout}>LOGOUT</button>
                            </li>
                        </>
                    ) : (
                        <>
                            <li className="list-group-item">
                                <Link className="nav-link" to="/login">LOGIN</Link>
                            </li>
                            <li className="list-group-item">
                                <Link className="nav-link" to="/register">REGISTER</Link>
                            </li>
                        </>
                    )}
                </ul>
            </div>

        </div>
    );
}
export default Navbar;
















