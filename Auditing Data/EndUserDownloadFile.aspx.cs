using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;
using System.IO;
using System.Net;
using System.Net.Mail;

public partial class EndUserDownloadFile : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {
        if (Session["UserMailID"] != null && Session["UserName"] != null)
        {
            TextBox1.Text = Session["UserMailID"].ToString();
            TextBox2.Text = Session["UserName"].ToString();
        }
    }
    SqlConnection con = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con1 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con2 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con3 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd;
    SqlCommand cmd1;
    SqlCommand cmd2;
    SqlDataAdapter da;
    DataTable dt = new DataTable();
    SqlDataReader dr;
    SqlDataReader dr1;
    SqlDataReader dr2;
    SqlDataReader dr4;
    DataSet ds = new DataSet();
    SqlConnection con4 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd4;


    private void mail()
    {
        string to = TextBox1.Text;
        string from = "mvkvicknesh@gmail.com";
        string pwd = t.Text;
        string subject = "Audit Data";
        string body = "Your File";
        using (MailMessage mm = new MailMessage(from, to))
        {
            mm.Subject = subject;
            mm.Body = body;

            string FileName = TextBox3.Text + ".txt";
            //  mm.Attachments.Add(new Attachment("~/images/" + txtCode.Text + ".jpg", FileName));
            mm.Attachments.Add(new Attachment(Request.MapPath("~/Files/" + TextBox3.Text + ".txt")));

            mm.IsBodyHtml = false;
            SmtpClient smtp = new SmtpClient();
            smtp.Host = "smtp.gmail.com";
            smtp.EnableSsl = true;
            NetworkCredential NetworkCred = new NetworkCredential(from, pwd);
            smtp.UseDefaultCredentials = true;
            smtp.Credentials = NetworkCred;
            smtp.Port = 587;
            smtp.Send(mm);
            ClientScript.RegisterStartupScript(GetType(), "alert", "alert('File sent.');", true);
        }
    }


    protected void Button3_Click(object sender, EventArgs e)
    {
        Panel1.Visible = false;
        con.Open();
        cmd = new SqlCommand("select * from FileRequest where FileName='" + TextBox3.Text + "' and FileKey='Waiting' and UserMailID='" + TextBox1.Text + "'", con);
        dr = cmd.ExecuteReader();
        if (dr.Read())
        {
            con4.Open();
            cmd4 = new SqlCommand("insert into AuditDetails values('" + TextBox3.Text + "','Key Mismatch','" + TextBox2.Text + "','" + DateTime.Now.ToString() + "')", con4);
            cmd4.ExecuteNonQuery();
            con4.Close();
            Response.Write("<script>alert('Invalid Key')</script>");
        }
        else
        {
            con2.Open();
            cmd2 = new SqlCommand("select * from FileRequest where FileName='" + TextBox3.Text + "' and FileKey='" + TextBox4.Text + "'and UserMailID='" + TextBox1.Text + "'", con2);
            dr2 = cmd2.ExecuteReader();
            if (dr2.Read())
            {
                con4.Open();
                cmd4 = new SqlCommand("insert into AuditDetails values('" + TextBox3.Text + "','File Sent To Mail','" + TextBox2.Text + "','" + DateTime.Now.ToString() + "')", con4);
                cmd4.ExecuteNonQuery();
                con4.Close();
               
                mail();


            }
            else
            {
                con4.Open();
                cmd4 = new SqlCommand("insert into AuditDetails values('" + TextBox3.Text + "','Key Mismatch','" + TextBox2.Text + "','" + DateTime.Now.ToString() + "')", con4);
                cmd4.ExecuteNonQuery();
                con4.Close();
                Response.Write("<script>alert('Invalid Key')</script>");
            }
            con2.Close();
        }
        con.Close();

       
    }

    protected void Button1_Click(object sender, EventArgs e)
    {
        con.Open();
        cmd = new SqlCommand("select * from FileRequest where FileName='" + TextBox3.Text + "' and FileKey='Waiting' and UserMailID='" + TextBox1.Text + "'", con);
        dr = cmd.ExecuteReader();
        if (dr.Read())
        {
            con4.Open();
            cmd4 = new SqlCommand("insert into AuditDetails values('" + TextBox3.Text + "','Key Mismatch','" + TextBox2.Text + "','" + DateTime.Now.ToString() + "')", con4);
            cmd4.ExecuteNonQuery();
            con4.Close();
            Response.Write("<script>alert('Invalid Key')</script>");
        }
        else
        {
            con2.Open();
            cmd2 = new SqlCommand("select * from FileRequest where FileName='" + TextBox3.Text + "' and FileKey='" + TextBox4.Text + "'and UserMailID='" + TextBox1.Text + "'", con2);
            dr2 = cmd2.ExecuteReader();
            if (dr2.Read())
            {
                con4.Open();
                cmd4 = new SqlCommand("insert into AuditDetails values('" + TextBox3.Text + "','File View','" + TextBox2.Text + "','" + DateTime.Now.ToString() + "')", con4);
                cmd4.ExecuteNonQuery();
                con4.Close();
                Panel1.Visible = true;
                StreamReader sr = new StreamReader(Server.MapPath("~/FilesE/" + TextBox3.Text + ".txt"));
                string read = sr.ReadToEnd();
                TextBox5.Text = read;
                sr.Close();
            }
            else
            {
                con4.Open();
                cmd4 = new SqlCommand("insert into AuditDetails values('" + TextBox3.Text + "','Key Mismatch','" + TextBox2.Text + "','" + DateTime.Now.ToString() + "')", con4);
                cmd4.ExecuteNonQuery();
                con4.Close();
                Response.Write("<script>alert('Invalid Key')</script>");
            }
            con2.Close();
        }
        con.Close();



           
    }

    protected void Button2_Click(object sender, EventArgs e)
    {
        Panel1.Visible = false;
        con.Open();
        cmd = new SqlCommand("select * from FileRequest where FileName='" + TextBox3.Text + "' and FileKey='Waiting' and UserMailID='" + TextBox1.Text + "'", con);
        dr = cmd.ExecuteReader();
        if (dr.Read())
        {
            con4.Open();
            cmd4 = new SqlCommand("insert into AuditDetails values('" + TextBox3.Text + "','Key Mismatch','" + TextBox2.Text + "','" + DateTime.Now.ToString() + "')", con4);
            cmd4.ExecuteNonQuery();
            con4.Close();
            Response.Write("<script>alert('Invalid Key')</script>");
        }
        else
        {
            con2.Open();
            cmd2 = new SqlCommand("select * from FileRequest where FileName='" + TextBox3.Text + "' and FileKey='" + TextBox4.Text + "'and UserMailID='" + TextBox1.Text + "'", con2);
            dr2 = cmd2.ExecuteReader();
            if (dr2.Read())
            {
                con4.Open();
                cmd4 = new SqlCommand("insert into AuditDetails values('" + TextBox3.Text + "','File Download','" + TextBox2.Text + "','" + DateTime.Now.ToString() + "')", con4);
                cmd4.ExecuteNonQuery();
                con4.Close();
               
                string n = TextBox3.Text;

                con1.Open();
                cmd1 = new SqlCommand("select FileName, ContentType, Data,UploadFile from FileUpload where FileName=@FileName", con1);
                cmd1.Parameters.AddWithValue("@FileName", n);
                dr1 = cmd1.ExecuteReader();
                if (dr1.Read())
                {

                    con4.Open();
                    cmd4 = new SqlCommand("update FileRequest set FileKey='Waiting',Status='Download' where FileName='" + TextBox3.Text + "' and FileKey='" + TextBox4.Text + "'and UserMailID='" + TextBox1.Text + "'", con4);
                    cmd4.ExecuteNonQuery();
                    con4.Close();

                    string filename = dr1[0].ToString();
                    string filetype = dr1[3].ToString();
                    string ext = Path.GetExtension(filetype);

                    //con2.Open();
                    //cmd2 = new SqlCommand("delete from SecretKey where UserMailID='" + Mail + "' and FileName='" + filename + "'", con2);
                    //cmd2.ExecuteNonQuery();
                    //con2.Close();

                    Response.ContentType = dr1["ContentType"].ToString();
                    Response.AddHeader("Content-Disposition", "attachment;filename=\"" + dr1["FileName"] + ext);
                    Response.BinaryWrite((byte[])dr1["Data"]);
                    Response.End();
                }
                con1.Close();

            }
            else
            {
                con4.Open();
                cmd4 = new SqlCommand("insert into AuditDetails values('" + TextBox3.Text + "','Key Mismatch','" + TextBox2.Text + "','" + DateTime.Now.ToString() + "')", con4);
                cmd4.ExecuteNonQuery();
                con4.Close();
                Response.Write("<script>alert('Invalid Key')</script>");
            }
            con2.Close();
        }
        con.Close();



        
    }
}