const nodeMailer = require("nodemailer");

exports.sendEmail = async (options) => {


  const transporter = nodeMailer.createTransport({

    // YOU CAN USE ANYTHING LIKE : GOOGLE, OUTLOOK, YAHOO,
    // OR ANY MAIL TESTING PROVIDER

    // here we have uses a mail tesing platform mailtrap.io

    // host: "smtp.mailtrap.io",
    // port: 2525,
    // auth: {
    //   user: "fdf6e7f2d8a1f6",
    //   pass: "7b1a7969d78b3a",
    // },

    // When we use google
      service: process.env.SMPT_SERVICE,

    auth: {
      user: process.env.SMPT_MAIL,
      pass: process.env.SMPT_PASSWORD,
    },
    host: process.env.SMPT_HOST,
    port: process.env.SMPT_PORT,
  

  });

  const mailOptions = {
    from: process.env.SMPT_MAIL,
    to: options.email,
    subject: options.subject,
    text: options.message,
  };

  await transporter.sendMail(mailOptions);
};
