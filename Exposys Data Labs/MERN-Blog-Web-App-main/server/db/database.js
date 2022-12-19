const mongoose = require('mongoose');


const connectDatabase = async ()=> {
    try{
        const conn = await mongoose.connect(process.env.CONNECTION_URL,{
            useNewUrlParser: true,
            // useCreateIndex: true,
            // useFindAndModify: false,
            useUnifiedTopology: true
        
        });
        console.log(`MongoDB Connected: ${conn.connection.host}`);
        
    }catch(err){
        console.log(err);
        console.error(`Error in connecting to database: ${err.message}`);
        process.exit(1);

    }
}
module.exports = connectDatabase;