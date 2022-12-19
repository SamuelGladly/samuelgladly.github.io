
import ArrowCircleRightIcon from '@mui/icons-material/ArrowCircleRight';
import { useDispatch } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { getPostsByTags } from '../../actions/post';
import background from "../../images/background-img/hh.jpg";
import background2 from "../../images/background-img/tee.jpg";
import background3 from "../../images/background-img/spoo.jpg";
import background4 from "../../images/background-img/bussss.jpg";



import { FontAwesomeIcon } from '@fortawesome/react-fontawesome' 
// import {faRigh} from '@fortawesome/free-regular-svg-icons'
import {faArrowRight} from '@fortawesome/free-solid-svg-icons'


import "./categorysecc.css"
const Categorysec = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();




    const handlecatfir1 = () =>{
        dispatch(getPostsByTags("Health"))
        navigate(`/tag/posts`);
    };
    const handlecatfir2 = () =>{
        dispatch(getPostsByTags("Technology"))
        navigate(`/tag/posts`);
    };
    const handlecatfir3 = () =>{
        dispatch(getPostsByTags("Sports"))
        navigate(`/tag/posts`);
    };
    const handlecatfir4 = () =>{
        dispatch(getPostsByTags("Business"))
        navigate(`/tag/posts`);
    };
    
    const myStyle1={
        // backgroundImage: 'linearGradient( rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7) ),url("../../images/background-img/Health.jpg")',
        // background : `linear-gradient(rgba(0, 0, 0, 0.7),  rgba(0, 0, 0, 0.7)), url(${background});`,
        backgroundImage : `url(${background})`,
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
        
    };
    const myStyle2={
        backgroundImage: `url(${background2})`,
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
        
    };
    const myStyle3={
        backgroundImage: `url(${background3})`,
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
    };
    const myStyle4={
        backgroundImage: `url(${background4})`,
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
    };

    return (
        <div>
            <div className="catt-window-sec">
                

                <div className='catwindchildd' style={myStyle1} onClick={handlecatfir1}>
                    <h5>Health  <FontAwesomeIcon icon={faArrowRight} className='righiconn'  /> </h5>
                </div>

                
                <div className='catwindchildd'style={myStyle2} onClick={handlecatfir2}>
                   <h5>Technology  <FontAwesomeIcon icon={faArrowRight} className='righiconn'  />  </h5>
                </div>
                <div className='catwindchildd' style={myStyle3} onClick={handlecatfir3}>
                    <h5>Sports<FontAwesomeIcon icon={faArrowRight} className='righiconn'  />  </h5>
                </div>
                <div className='catwindchildd'style={myStyle4} onClick={handlecatfir4}>
                    <h5>Business<FontAwesomeIcon icon={faArrowRight} className='righiconn'  />  </h5>
                </div>
            </div>
            
            
         

        </div>
    )
}

export default Categorysec;