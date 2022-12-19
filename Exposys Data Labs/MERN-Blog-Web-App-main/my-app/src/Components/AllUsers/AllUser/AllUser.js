
import { useNavigate } from "react-router-dom";

import "./allst.css"
const AllUser = (props) => {
    const navigate = useNavigate();

    const handleUser = (id) => {
        navigate(`/user/${props.user._id}`);
    }
    if(props.user.image?.url){
        var imgloc = props.user.image?.url
        
    }else {
       var imgloc = "https://res.cloudinary.com/dayypkgas/image/upload/v1662312587/users/user-removebg-preview_tsnhjg.png"
    }    

    return (
        <div className="top-user-pro-main">
            <hr></hr>
            <div onClick={handleUser} className='mainusss'>
                <div className='avatar-imgg'>
                    <img src={imgloc} alt="post img" className="user-imgg-ava" />
                </div>
                <div className='userdetailss'>
                    <h5>{props.user.name}</h5>
                    <p>{props.user.profession}</p>
                    <p>{props?.user?.bio?.split(' ').splice(0,6).join(' ')}...</p>
                </div>
            </div>        
            
        </div>
    );
}
export default AllUser;