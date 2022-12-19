import "./formm.css"
import { useState, useEffect, useRef } from "react";
import { useDispatch } from "react-redux";
import { createPost } from "../../actions/post";

import {TextField, MenuItem, Button} from "@material-ui/core";
import ChipInput from 'material-ui-chip-input';

import { useNavigate } from "react-router-dom";





const Form = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();

  const editor = useRef(null)
  const [image, setImage] = useState(null);

  const [content, setContent] = useState('')

  const [postData, setPostData] = useState({
      title: "",
      shortDescription : "",
      message: "",
      category: "",
      tags: []
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
    const formHandler = (e) => {
        e.preventDefault();
        const {title,shortDescription, message, category, tags} = postData;
        dispatch(createPost(title,shortDescription, message, category, tags, image, navigate));
    }
    const contentFieldChange = (data) => {
      setContent(data);
    }
    useEffect(() => {
      document.title = 'Write';
  });
  


    return (
        <div className="form-start">
          <div className="form-main">
            <div className="form-window">
              <form onSubmit={formHandler}>

        <TextField
          name="title"
          variant="outlined"
          label="Title"
          fullWidth
          required
          margin="normal"
          value={postData.title}
          onChange={(e) => setPostData({ ...postData, title: e.target.value })}
          InputProps={{ style: { fontSize: 17 } }}
          InputLabelProps={{ style: { fontSize: 17 } }}
        />
        <TextField
          name="shortDescription"
          variant="outlined"
          label="Short Description"
          fullWidth
          margin="normal"
          
          value={postData.shortDescription}
          onChange={(e) => setPostData({ ...postData, shortDescription: e.target.value })}
          InputProps={{ style: { fontSize: 17 } }}
          InputLabelProps={{ style: { fontSize: 17 } }}
        />
        <TextField
          name="message"
          variant="outlined"
          label="Start Your Blog ..."
          fullWidth
          required
          multiline
          margin="normal"
          rows={20}
          value={postData.message}
          onChange={(e) =>
            setPostData({ ...postData, message: e.target.value })
          }
          InputProps={{ style: { fontSize: 17 } }}
          InputLabelProps={{ style: { fontSize: 17 } }}
        />

        {/* <JoditEditor 
          ref={editor}
          value={content}
          onChange={(newContent) => contentFieldChange(newContent)}
        /> */}
        <TextField
          label="Select Category"
          select
          fullWidth
          margin="normal"
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
          fullWidth
          margin="normal"
          value={postData.tags}
          onAdd={(chip) => handleAddChip(chip)}
          onDelete={(chip) => handleDeleteChip(chip)}
          InputProps={{ style: { fontSize: 17 } }}
          InputLabelProps={{ style: { fontSize: 17 } }}
        />

        {image && <img src={image} alt="post" className="upload-iimgg" />}
        <input type="file" accept="image/*"  onChange={handleImageChange} />
        <p>image is required**</p>
        <Button
          variant="contained"
          color="primary"
          size="large"
          margin='normal'
          style={{ lineHeight: "30px", fontSize:'20px', backgroundColor:'#feb98e',textTransform:'capitalize',margin:'10px 0px',color:'black'  }}
          type="submit"
          fullWidth
        >
          Submit
        </Button>
        </form>

        </div>
        </div>
        </div>
    );
}
export default Form;












