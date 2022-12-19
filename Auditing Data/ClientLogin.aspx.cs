using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;


public partial class ClientLogin : System.Web.UI.Page
{
    protected void Page_Load(object sender, EventArgs e)
    {

    }

    SqlConnection con = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con1 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con2 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlConnection con3 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd;
    SqlDataAdapter da;
    DataTable dt = new DataTable();
    SqlDataReader dr;
    SqlDataReader dr1;
    SqlDataReader dr2;
    DataSet ds = new DataSet();
    SqlConnection con4 = new SqlConnection(ConfigurationManager.ConnectionStrings["Audit"].ConnectionString.ToString());
    SqlCommand cmd4;

    protected void Button1_Click(object sender, EventArgs e)
    {
        

        con1.Open();
        cmd = new SqlCommand("select * from ClientDetails where MailID='" + TextBox1.Text + "' and Password='" + TextBox2.Text + "' ", con1);
        dr1 = cmd.ExecuteReader();
        if (dr1.Read())
        {
            con2.Open();
            cmd = new SqlCommand("select * from ClientDetails where MailID='" + TextBox1.Text + "' and Password='" + TextBox2.Text + "' and Status='Accept' ", con2);
            dr2 = cmd.ExecuteReader();
            if (dr2.Read())
            {
                Session["ClientName"] = dr2[3].ToString();
                Session["ClientMailID"] = TextBox1.Text;
                Response.Redirect("ClientHome.aspx");
            }
            else
            {
                Response.Write("<script>alert('Not Authenticated')</script>");
            }
            con2.Close();
        }
        else
        {
            Response.Write("<script>alert('Invalid Data')</script>");
        }
        con1.Close();
    }
}