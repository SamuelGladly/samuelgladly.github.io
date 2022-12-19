using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;

public partial class ClientRegister : System.Web.UI.Page
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

    private void clear()
    {
        TextBox1.Text = "";
        TextBox2.Text = "";
        TextBox3.Text = "";
        TextBox4.Text = "";
        TextBox5.Text = "";
    }

    protected void Button1_Click(object sender, EventArgs e)
    {
        string date = DateTime.Now.ToString("dd/MM/yyyy");

        con1.Open();
        cmd = new SqlCommand("select * from ClientDetails where MobileNumber='" + TextBox5.Text + "'", con1);
        dr1 = cmd.ExecuteReader();
        if (dr1.Read())
        {
            Response.Write("<script>alert('MobileNo already Exist')</script>");
        }
        else
        {
            con2.Open();
            cmd = new SqlCommand("select * from ClientDetails where MailID='" + TextBox1.Text + "'", con2);
            dr2 = cmd.ExecuteReader();
            if (dr2.Read())
            {
                Response.Write("<script>alert('MailId already Exist')</script>");
            }
            else
            {
                con3.Open();
                cmd = new SqlCommand("insert into ClientDetails values('" + TextBox1.Text + "','" + TextBox2.Text + "','" + TextBox4.Text + "','" + TextBox5.Text + "','" + date + "','Waiting')", con3);
                cmd.ExecuteNonQuery();
                con3.Close();


                Response.Write("<script>alert('Submited Successfully')</script>");


            }

            con2.Close();
        }

        con1.Close();


        con.Close();

        clear();
    }
}