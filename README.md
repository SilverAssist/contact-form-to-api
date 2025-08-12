# Contact Form 7 to API

**🚀 Connect your Contact Form 7 forms to any API effortlessly!**

Transform your WordPress contact forms into powerful data collection tools that automatically send submissions to your favorite CRM, email marketing service, or any external API endpoint.

[![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-blue)](https://wordpress.org)
[![Contact Form 7](https://img.shields.io/badge/Contact%20Form%207-Required-green)](https://wordpress.org/plugins/contact-form-7/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)](LICENSE)

---

## ✨ What This Plugin Does

**Contact Form 7 to API** seamlessly connects your existing Contact Form 7 forms with external APIs. No coding required – just configure, map your fields, and you're ready to go!

### 🎯 Perfect For:
- **CRM Integration**: Send leads directly to Salesforce, HubSpot, Pipedrive, etc.
- **Email Marketing**: Add subscribers to Mailchimp, ConvertKit, ActiveCampaign
- **Custom Applications**: Connect to your own business applications and databases
- **Third-Party Services**: Integrate with Slack, Zapier, webhooks, and more

---

## 🌟 Key Features

### 🔧 **Easy Setup**
- **No Code Required**: Configure everything through a simple interface
- **Form-Specific Settings**: Each form can have its own API configuration
- **Live Testing**: Test your API connections before going live

### 🔄 **Flexible Data Mapping**
- **Field Mapping**: Map any Contact Form 7 field to any API parameter
- **Multiple Formats**: Send data as JSON, XML, or form parameters
- **Custom Headers**: Add authentication tokens, API keys, and custom headers

### 🚀 **Reliable Delivery**
- **Multiple HTTP Methods**: Support for GET, POST, PUT, PATCH requests
- **Retry Logic**: Automatic retries if API calls fail
- **Error Handling**: Graceful handling of API errors without breaking your forms

### 📊 **Monitoring & Debugging**
- **Debug Mode**: See exactly what data is being sent to your APIs
- **Error Logging**: Track failed submissions and troubleshoot issues
- **Success Tracking**: Monitor successful API calls and response data

---

## 🚀 Quick Start

### Step 1: Install & Activate
1. Install the plugin from WordPress admin or upload manually
2. Make sure **Contact Form 7** is installed and activated
3. Activate **Contact Form 7 to API**

### Step 2: Configure Your First Integration
1. **Edit your Contact Form 7 form**
2. Click on the **"API Integration"** tab
3. **Add your API endpoint URL**
4. **Choose HTTP method** (usually POST)
5. **Add headers** (like Authorization tokens)

### Step 3: Map Your Fields
1. **Select form fields** you want to send
2. **Map them to API parameters**
3. **Set data format** (JSON, XML, or form data)

### Step 4: Test & Go Live
1. **Use the test feature** to verify everything works
2. **Save your configuration**
3. **Your form is now connected!** 🎉

---

## 💡 Real-World Examples

### Example 1: Send Leads to Your CRM
```
Form Fields → API Parameters
-----------------------------
[your-name]    → customer_name
[your-email]   → email_address  
[your-phone]   → phone_number
[your-message] → inquiry_details
```

### Example 2: Add Email Subscribers
```
API Endpoint: https://api.mailchimp.com/3.0/lists/{list-id}/members
Method: POST
Headers: Authorization: apikey your-api-key

Field Mapping:
[your-email] → email_address
[your-name]  → merge_fields.FNAME
```

### Example 3: Slack Notifications
```
API Endpoint: https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
Method: POST

Field Mapping:
Static: "New contact form submission" → text
[your-name] → attachments.fields.title
[your-message] → attachments.fields.value
```

---

## 🛠️ Supported Integrations

### 📈 **CRM Systems**
- Salesforce
- HubSpot  
- Pipedrive
- Zoho CRM
- Custom CRM APIs

### 📧 **Email Marketing**
- Mailchimp
- ConvertKit
- ActiveCampaign
- Constant Contact
- AWeber

### 💬 **Communication Tools**
- Slack
- Discord
- Microsoft Teams
- Custom webhooks

### 🔗 **Automation Platforms**
- Zapier
- Integromat/Make
- IFTTT
- Custom applications

---

## 📋 Requirements

- **WordPress**: 6.5 or higher
- **PHP**: 8.0 or higher  
- **Contact Form 7**: Latest version
- **SSL/HTTPS**: Recommended for secure API communications

---

## ⚙️ Installation

### Via WordPress Admin (Recommended)
1. Go to **Plugins → Add New** in your WordPress admin
2. Search for **"Contact Form 7 to API"**
3. Click **Install Now** → **Activate**

### Manual Installation
1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. **Activate** the plugin
---

## 📚 How to Configure

### Step-by-Step Configuration Guide

#### 1. **Access Form Settings**
- Go to **Contact → Contact Forms** in WordPress admin
- **Edit** the form you want to connect to an API
- Click on the **"API Integration"** tab

#### 2. **Basic API Settings**
```
✅ API Endpoint URL: https://your-api.com/endpoint
✅ HTTP Method: POST (most common)
✅ Data Format: JSON (recommended)
```

#### 3. **Add Authentication**
**For Bearer Token:**
```
Header Name: Authorization  
Header Value: Bearer your-token-here
```

**For API Key:**
```
Header Name: X-API-Key
Header Value: your-api-key-here
```

#### 4. **Map Your Fields**
| Contact Form Field | API Parameter | Example |
|-------------------|---------------|---------|
| `[your-name]` | `name` | John Doe |
| `[your-email]` | `email` | john@example.com |
| `[your-phone]` | `phone` | +1234567890 |
| `[your-message]` | `message` | Hello world! |

#### 5. **Test Your Setup**
1. **Use the "Test Connection" button**
2. **Fill out and submit your form**  
3. **Check the debug logs** if something doesn't work
4. **Verify data reaches your API**

---

## 🔧 Common Use Cases

### 🏢 **CRM Integration**
**Connect leads directly to your CRM system**

**Popular CRMs:**
- **Salesforce**: Use REST API with OAuth authentication
- **HubSpot**: Simple API key authentication  
- **Pipedrive**: REST API with personal token
- **Zoho CRM**: OAuth 2.0 integration

**Typical Setup:**
```
Endpoint: https://api.hubspot.com/contacts/v1/contact
Method: POST
Auth: X-API-KEY header
```

### 📧 **Email Marketing**
**Add subscribers automatically**

**Popular Services:**
- **Mailchimp**: Add to audience lists
- **ConvertKit**: Create subscribers and add tags
- **ActiveCampaign**: Add contacts and trigger automations

**Typical Setup:**
```
Endpoint: https://your-account.api.mailchimp.com/3.0/lists/{list-id}/members  
Method: POST
Auth: Authorization: apikey your-key
```

### 💬 **Notifications**
**Get instant alerts about new submissions**

**Popular Services:**
- **Slack**: Webhook notifications to channels
- **Discord**: Bot messages to servers  
- **Microsoft Teams**: Connector webhooks

**Typical Setup:**
```
Endpoint: https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
Method: POST  
Format: JSON with text and attachments
```

---

## �️ Troubleshooting

### ❌ **Common Problems**

#### **"API Connection Failed"**
- ✅ Check your API endpoint URL (copy-paste from API docs)
- ✅ Verify your API credentials are correct
- ✅ Make sure your server can reach the external API
- ✅ Check if the API requires specific headers

#### **"Data Not Appearing in API"**
- ✅ Verify field mapping (form field names must match exactly)
- ✅ Check data format (JSON vs form parameters)
- ✅ Review API documentation for required fields
- ✅ Enable debug mode to see what's being sent

#### **"Form Submission Slow"**
- ✅ Reduce API timeout settings
- ✅ Check if API server is responding slowly
- ✅ Consider using asynchronous processing
- ✅ Monitor API response times

### 🔍 **Debug Mode**
Enable detailed logging to troubleshoot issues:

1. **Go to the API Integration tab**
2. **Enable "Debug Mode"**
3. **Submit a test form**
4. **Review the debug log** for detailed information

### 📞 **Getting Help**

**Before asking for help:**
- ✅ Check this README file
- ✅ Review API documentation
- ✅ Test with debug mode enabled
- ✅ Try with a simple test API first

**Where to get help:**
- 🐛 [Report Bugs on GitHub](https://github.com/SilverAssist/contact-form-to-api/issues)

---

## 🎯 Pro Tips

### ✨ **Best Practices**
- **Test First**: Always test with a sample API before going live
- **Use HTTPS**: Secure API endpoints protect sensitive data
- **Monitor Logs**: Regularly check for failed submissions
- **Backup Settings**: Export your configuration before making changes

### 🚀 **Advanced Tips**
- **Multiple APIs**: Send the same form to multiple services
- **Conditional Logic**: Send to different APIs based on form values  
- **Custom Fields**: Map static values and computed fields
- **Error Handling**: Set up fallback actions for failed API calls

---

## � **What's Next?**

After setting up your first integration:

1. **📊 Monitor Performance**: Check success rates and response times
2. **🔄 Add More Integrations**: Connect to additional services
3. **⚙️ Optimize Settings**: Fine-tune timeouts and retry logic
4. **📱 Test Mobile**: Ensure forms work on all devices
5. **🔒 Review Security**: Verify API credentials are secure

---

## 🏆 **Why Choose This Plugin?**

### ✅ **User-Friendly**
- No coding required
- Visual configuration interface
- Built-in testing tools
- Clear documentation

### ✅ **Reliable**
- Automatic retries on failures
- Comprehensive error logging  
- Performance monitoring
- Secure data handling

### ✅ **Flexible**
- Works with any API
- Multiple data formats
- Custom authentication methods
- Conditional processing

### ✅ **Supported**
- Regular updates
- Community support
- Detailed documentation  
- Open source

---

## 📄 License & Credits

**License**: GPL-2.0+ - You're free to use, modify, and distribute this plugin.

**Created by**: [Silver Assist](https://silverassist.com) - WordPress development experts.

---

**Made with ❤️ by [Silver Assist](https://silverassist.com)**
