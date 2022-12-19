
import { useDispatch, useSelector } from "react-redux";
import { useState, useEffect } from "react";

import { Link, useNavigate } from "react-router-dom";
import { updateProfile } from "../../actions/user";
import {TextField, MenuItem, Button} from "@material-ui/core";

const Update = () => {
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const { user } = useSelector((state) => state.user);

    
    // const [userData , setUserData] = useState({
    //     name:"",
    //     email:"",
    //     bio:"",
    //   });
    const [name, setName] = useState(user.name);
    const [email, setEmail] = useState(user.email);
    const [bio, setBio] = useState(user.bio);
    const [profession, setProfession] = useState(user.profession);

    const [image, setImage] = useState(null);

    const handleImageChange = (e) => {
        const file = e.target.files[0];
    
        const Reader = new FileReader();
        Reader.readAsDataURL(file);
    
        Reader.onload = () => {
          if (Reader.readyState === 2) {
            setImage(Reader.result);
          }
        };
      };

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log(name, email, bio, profession);   
        dispatch(updateProfile(name,email,bio, profession, image,navigate)) 
    }
    return(
        <div>

            <div className="update-start">
            <div className="update-main">
                <div className="update-window">
                    <form method="POST" onSubmit={handleSubmit}>
                        <TextField
                            name="name"
                            variant="outlined"
                            label="Name"
                            fullWidth
                            margin="normal"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            InputProps={{ style: { fontSize: 17 } }}
                            InputLabelProps={{ style: { fontSize: 17 } }}
                        />

                        <TextField
                            name="email"
                            variant="outlined"
                            label="Email"
                            fullWidth
                            margin="normal"
                            value={email}
                            
                            onChange={(e) => setEmail(e.target.value)}
                            InputProps={{ style: { fontSize: 17 } }}
                            InputLabelProps={{ style: { fontSize: 17 } }}
                        />
                        <TextField
                            name="bio"
                            variant="outlined"
                            label="Bio"
                            fullWidth
                            margin="normal"
                            value={bio}
                            onChange={(e) => setBio(e.target.value)}
                            InputProps={{ style: { fontSize: 17 } }}
                            InputLabelProps={{ style: { fontSize: 17 } }}
                        />

                        <TextField
                            name="profession"
                            variant="outlined"
                            label="Profession"
                            fullWidth
                            margin="normal"
                            value={profession}
                            onChange={(e) => setProfession(e.target.value)}
                            InputProps={{ style: { fontSize: 17 } }}
                            InputLabelProps={{ style: { fontSize: 17 } }}
                        />
                        {image && <img src={image} alt="post" className="upload-iimgg" />}
                        <input type="file" accept="image/*" onChange={handleImageChange} />

                            <Button
                                variant="contained"
                                color="primary"
                                size="large"
                                margin='normal'
                                style={{ lineHeight: "30px", fontSize:'20px', backgroundColor:'#f6a573',textTransform:'capitalize',margin:'10px 0px'  }}
                                type="submit"
                                fullWidth
                            >
                            Submit
                            </Button>
                    </form>

                    <div className="up-password">
                    <Link className="update-proff-my" to="/update/password">Update Password</Link>
                    
                </div>
                </div>
            </div>
            </div>
        </div>
    )
};
export default Update;

