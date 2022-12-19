
import { useNavigate } from "react-router-dom";

import "./sidtitless.css"

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome' 
import {faCalendarDays} from '@fortawesome/free-regular-svg-icons'

const SideTitle = (props) => {
    const navigate = useNavigate();

   

    const handleClick = (id) => {
        navigate(`/posts/${props.post._id}`);
    }
    return (
        <div className="side-titles-mainnn">
            <hr></hr>
            <div onClick={handleClick} className='sidmainn'>
                <div className="posttimgg">
                    <img src={props.post.image?.url} alt="post img" className="imgggpost" />
                </div>
                <div className="post-heading-details">
                <h4>{props.post.title.split(' ').splice(0,5).join(' ')}...</h4>
                    <p><FontAwesomeIcon icon={faCalendarDays} className='calender-deta'  />
                        {new Date(props.post.createdAt).toLocaleDateString('en-US',{day :'2-digit' })}
                        <span className="midd-mann">
                        {new Date(props.post.createdAt).toLocaleDateString('en-US',{month:'short'})}
                        </span>
                        {new Date(props.post.createdAt).getFullYear()}
                    </p>
                    
                </div>
            </div>
        </div>
    )
}

export default SideTitle;