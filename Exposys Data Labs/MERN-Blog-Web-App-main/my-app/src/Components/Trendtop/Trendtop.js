import { useDispatch, useSelector } from "react-redux";
import { useNavigate } from "react-router-dom";
import CategoryButton from "../CategoryButton/CategoryButton";
import CalendarMonthIcon from '@mui/icons-material/CalendarMonth';
import BookmarkIcon from '@mui/icons-material/Bookmark';
import FavoriteOutlinedIcon from '@mui/icons-material/FavoriteOutlined';
import { savePost } from "../../actions/post";


import { FontAwesomeIcon } from '@fortawesome/react-fontawesome' 
import {faCalendarDays} from '@fortawesome/free-regular-svg-icons'


import "./trendtopp.css";
const Trendtop = (props) => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    

    const {isAuthenticated, user} = useSelector((state) => state.user);

    const openPost = () => {
        navigate(`/posts/${props.post._id}`);
    }

    const openUser = () => {
        navigate(`/user/${props.post.owner}`);
    }
    const savePosthandler = () => {
        if(!isAuthenticated){
            alert("Login First");
        }else{
            dispatch(savePost(props.post._id));
        }
    }

    return (
        <div className="trendDivv">
            <div className="trend-right-window">

                <div className="trend-leff-side" onClick={openPost}>
                    <img src={props.post.image.url} alt="post-img" className="postt-img-left" />
                </div>

                <div className="trend-right-side">
                    <div className="trend-rig-top">
                        <CategoryButton catt={props.post.category} />
                        {/* <button className="catt-btn-top-rg" >{props.post.category}</button> */}
                    </div>
                    <div className="trend-rig-midd">
                    <h3>{props.post.title.split(' ').splice(0,5).join(' ')}.</h3>
                    <p>{props.post.shortDescription.split(' ').splice(0,5).join(' ')}..</p>
                    </div>
                    <div className="trend-rig-foott">

                        <p> <FontAwesomeIcon icon={faCalendarDays} className='calender-deta'  /> 
                            {new Date(props.post.createdAt).toLocaleDateString('en-US',{day :'2-digit' })}
                            {new Date(props.post.createdAt).toLocaleDateString('en-US',{month:'short'})}
                            {new Date(props.post.createdAt).getFullYear()}
                        </p>
                        <BookmarkIcon fontSize="large" onClick={savePosthandler} className="save-bttnn" />

                    </div>

                </div>
            </div>
            
        </div>
    )
}

export default Trendtop;