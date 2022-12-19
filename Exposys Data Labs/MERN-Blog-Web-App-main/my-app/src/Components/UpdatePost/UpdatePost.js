import "./updatepostt.css"
import { useState, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useNavigate, useParams } from "react-router-dom";

import {TextField, MenuItem, Button} from "@material-ui/core";
import ChipInput from 'material-ui-chip-input';

import { getPostDetails, updatePost } from "../../actions/post";


const UpdatePost = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    

    const {id} = useParams();
    console.log(id)

    useEffect(() => {
        dispatch(getPostDetails(id));
    },[])
    // Getting the post data which we want to update
    const {posts} = useSelector((state) => state.post);
        
    const [image, setImage] = useState(null);

    const [postData, setPostData] = useState({
        title: posts.title,
        message: posts.message,
        category: posts.category,
        tags: posts.tags
    });


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
    
    

    const handleAddChip = (tag) => {
        setPostData({ ...postData, tags: [...postData.tags, tag] });
    };
    
    const handleDeleteChip = (chipToDelete) => {
        setPostData({
          ...postData,
          tags: postData.tags.filter((tag) => tag !== chipToDelete)
        });
    };
    // console.log(postData);

    // const [title, setTitle] = useState();
    // const [message, setMessage ] = useState();
    // const [creator, setCreator] = useState();
    // const [tags, settags] = useState([]);

// console.log(category);
    // const contentFieldChange = (data) => {
    //   setContent(data);
    // }

// console.log(content);


    const formHandler = (e) => {
        e.preventDefault();
        // console.log(postData);
        const {title, message, category, tags} = postData;
        dispatch(updatePost(id,title,message,tags,category,image,navigate))
    }

    return (
        <div>
          <div className="update-main">
            <div className="update-window">
                <form onSubmit={formHandler}>
                <TextField
                  name="title"
                  variant="outlined"
                  margin="normal"
                  label="Title"
                  fullWidth
                  value={postData.title}
                  onChange={(e) => setPostData({ ...postData, title: e.target.value })}
                  InputProps={{ style: { fontSize: 17 } }}
                  InputLabelProps={{ style: { fontSize: 17 } }}
                />
                <TextField
                  name="message"
                  variant="outlined"
                  label="Message"
                  margin="normal"
                  fullWidth
                  multiline
                  rows={10}
                  value={postData.message}
                  onChange={(e) =>
                    setPostData({ ...postData, message: e.target.value })
                  }
                  InputProps={{ style: { fontSize: 17 } }}
                  InputLabelProps={{ style: { fontSize: 17 } }}
                />

                <TextField
                  label="Select Category"
                  select
                  margin="normal"
                  fullWidth
                  value={postData.category}
                  onChange={(e) =>
                    setPostData({ ...postData, category: e.target.value })
                  }
                  InputProps={{ style: { fontSize: 17 } }}
                  InputLabelProps={{ style: { fontSize: 17 } }}
                  // onChange={hadle}
                >
                <MenuItem value="Health">Health</MenuItem>
                <MenuItem value="Sports">Sports</MenuItem>
                <MenuItem value="Programming">Programming</MenuItem>
                <MenuItem value="Technology">Technology</MenuItem>
                <MenuItem value="Food">Food</MenuItem>
                <MenuItem value="Politics">Politics</MenuItem>
                <MenuItem value="Motivation">Motivation</MenuItem>
                <MenuItem value="Travel">Travel</MenuItem>
                <MenuItem value="Blockchain">Blockchain</MenuItem>
                <MenuItem value="Business">Business</MenuItem>
                <MenuItem value="Life">Life</MenuItem>
                <MenuItem value="Humanity">Humanity</MenuItem>
                </TextField>

                <ChipInput
                  name="tags"
                  variant="outlined"
                  label="Tags"
                  margin="normal"
                  fullWidth
                  value={postData.tags}
                  onAdd={(chip) => handleAddChip(chip)}
                  onDelete={(chip) => handleDeleteChip(chip)}
                  InputProps={{ style: { fontSize: 17 } }}
                  InputLabelProps={{ style: { fontSize: 17 } }}
                />

                {image && <img src={image} alt="post" className="upload-iimgg" />}
                <input type="file" accept="image/*" onChange={handleImageChange} />
                <Button
                  variant="contained"
                  color="primary"
                  size="large"
                  margin="normal"
                  style={{ lineHeight: "30px", fontSize:'20px', backgroundColor:'#feb98e',textTransform:'capitalize',margin:'10px 0px',color:'black'  }}
                  type="submit"
                  fullWidth
                >
                  Update Post
                </Button>
                </form>
        </div>
      </div>
    </div>
    );
}
export default UpdatePost;





