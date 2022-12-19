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

public partial class KGCUserRequest : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {

    }
    SqlConnection con = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con1 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con2 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con3 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd;
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

    protected void LinkButton2_Click(object sender, EventArgs e)
    {
        LinkButton lnkbtn = sender as LinkButton;
        GridViewRow gvrow = lnkbtn.NamingContainer as GridViewRow;

        string a = gvrow.Cells[0].Text;
        string b= gvrow.Cells[1].Text;
        string c = gvrow.Cells[3].Text;
        string mail = null;

        con2.Open();
        cmd2 = new SqlCommand("select UserMailID from FileRequest where UserName='"+a+"' and FileName='" +b+ "' and Date='"+c+"'", con2);
        dr2 = cmd2.ExecuteReader();
        if (dr2.Read())
        {
            mail = dr2[0].ToString();
        }
        else
        {

        }
        con2.Close();

        con3.Open();
        cmd = new SqlCommand("update FileRequest set Status='Blocked' where UserMailID='"+mail+"'and FileName='"+b+"'", con3);
        cmd.ExecuteNonQuery();
        con3.Close();

        Response.Redirect("KGCUserRequest.aspx");
        Response.Write("<script>alert('Request Blocked Successfully')</script>");
    }

    protected void LinkButton1_Click(object sender, EventArgs e)
    {
        LinkButton lnkbtn = sender as LinkButton;
        GridViewRow gvrow = lnkbtn.NamingContainer as GridViewRow;

        string a = gvrow.Cells[0].Text;
        string b = gvrow.Cells[1].Text;
        string c = gvrow.Cells[3].Text;
        string mail = null;
        string key = null;

        con2.Open();
        cmd2 = new SqlCommand("select UserMailID from FileRequest where UserName='" + a + "' and FileName='" + b + "' and Date='" + c + "'", con2);
        dr2 = cmd2.ExecuteReader();
        if (dr2.Read())
        {
            mail = dr2[0].ToString();
        }
        else
        {

        }
        con2.Close();


        con4.Open();
        cmd4 = new SqlCommand("select FileKey from FileUpload where FileName='" + b + "'", con4);
        dr4 = cmd4.ExecuteReader();
        if (dr4.Read())
        {
            key = dr4[0].ToString();
        }
        else
        {

        }
        con4.Close();

        con3.Open();
        cmd = new SqlCommand("update FileRequest set FileKey='"+key+ "',Status='Key Sent' where UserMailID='" + mail + "'and FileName='" + b + "'", con3);
        cmd.ExecuteNonQuery();
        con3.Close();
        Response.Write("<script>alert('File Key Send')</script>");
        string to = mail;
        string from = "mvkvicknesh@gmail.com";
        string pwd = t.Text;
        string subject = "Audit Data";
        string body = "File Name : : "+b+"\n"+"File Key : : "+key;
        using (MailMessage mm = new MailMessage(from, to))
        {
            mm.Subject = subject;
            mm.Body = body;

            mm.IsBodyHtml = false;
            SmtpClient smtp = new SmtpClient();
            smtp.Host = "smtp.gmail.com";
            smtp.EnableSsl = true;
            NetworkCredential NetworkCred = new NetworkCredential(from, pwd);
            smtp.UseDefaultCredentials = true;
            smtp.Credentials = NetworkCred;
            smtp.Port = 587;
            smtp.Send(mm);
            ClientScript.RegisterStartupScript(GetType(), "alert", "alert('');", true);
        }


        
        Response.Redirect("KGCUserRequest.aspx");
    }
}