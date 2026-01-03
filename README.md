# CF7 to API

**🚀 Connect your Contact Form 7 forms to any API effortlessly!**

Transform your WordPress contact forms into powerful data collection tools that automatically send submissions to your favorite CRM, email marketing service, or any external API endpoint.

[![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-blue)](https://wordpress.org)
[![Contact Form 7](https://img.shields.io/badge/Contact%20Form%207-Required-green)](https://wordpress.org/plugins/contact-form-7/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-purple)](https://php.net)
[![License](https://img.shields.io/badge/license-Polyform--Noncommercial--1.0.0-blue.svg)](LICENSE)

> **📢 Trademark Notice:** This plugin extends the functionality of Contact Form 7. "Contact Form 7" is a trademark of Rock Lobster, LLC. This plugin is an independent extension and is not affiliated with, endorsed by, or sponsored by the Contact Form 7 project or Rock Lobster, LLC. We use "CF7" (an unofficial abbreviated form) in our plugin name in compliance with the [Contact Form 7 trademark policy](https://contactform7.com/trademark-policy/).

---

## ✨ What This Plugin Does

**CF7 to API** seamlessly connects your existing Contact Form 7 forms with external APIs. No coding required – just configure, map your fields, and you're ready to go!

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
- **Global Settings**: Configure plugin-wide retry limits, logging, and data retention
- **Live Testing**: Test your API connections before going live

### 🔄 **Flexible Data Mapping**
- **Field Mapping**: Map any Contact Form 7 field to any API parameter
- **Multiple Formats**: Send data as JSON, XML, or form parameters
- **Custom Headers**: Add authentication tokens, API keys, and custom headers

### 🚀 **Reliable Delivery**
- **Multiple HTTP Methods**: Support for GET, POST, PUT, PATCH requests
- **Retry Logic**: Automatic retries if API calls fail
- **Manual Retry**: Retry failed requests directly from the admin interface
- **Error Handling**: Graceful handling of API errors without breaking your forms

### 📊 **Advanced Monitoring & Analytics**
- **API Request Logs**: Complete history of all API requests with status, response times, and data
- **Dashboard Widget**: At-a-glance statistics on your WordPress dashboard
- **Date Range Filters**: Filter logs by today, yesterday, last 7/30 days, or custom ranges
- **Export Logs**: Export your API logs to CSV or JSON for analysis
- **Debug Mode**: See exactly what data is being sent to your APIs

### ⚙️ **Global Settings**
- **Retry Configuration**: Set maximum retries per request and hourly rate limits
- **Sensitive Data Protection**: Configure patterns for automatic data anonymization
- **Logging Control**: Enable/disable API logging globally
- **Log Retention**: Automatic cleanup of old logs (7, 14, 30, 60, or 90 days)

---

## 🚀 Quick Start

### Step 1: Install & Activate
1. Install the plugin from WordPress admin or upload manually
2. Make sure **Contact Form 7** is installed and activated
3. Activate **CF7 to API**

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

## 📊 Admin Dashboard & Monitoring

### Dashboard Widget
Once activated, a **CF7 to API Statistics** widget appears on your WordPress dashboard showing:
- **Total Requests**: Number of API calls in the last 24 hours
- **Success Rate**: Percentage of successful submissions
- **Avg Response Time**: Average API response time

### API Logs
Access detailed logs at **Settings → Silver Assist → CF7 to API → API Logs**:
- View all API requests with status (success, error, pending)
- Filter by date range (today, last 7 days, custom range)
- See full request/response data for debugging
- **Retry failed requests** with one click
- **Export logs** to CSV or JSON

### Global Settings
Configure plugin-wide settings at **Settings → Silver Assist → CF7 to API**:

| Setting | Description | Default |
|---------|-------------|----------|
| Max Retries per Entry | Maximum retry attempts for a single request | 3 |
| Max Retries per Hour | Global hourly rate limit for retries | 10 |
| Sensitive Patterns | Field patterns to anonymize (password, token, etc.) | Built-in list |
| Enable Logging | Turn API logging on/off | Enabled |
| Log Retention | Auto-delete logs older than X days | 30 days |

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
- **PHP**: 8.2 or higher
- **Contact Form 7**: Latest version
- **SSL/HTTPS**: Recommended for secure API communications

---

## ⚙️ Installation

### Via WordPress Admin (Recommended)
1. Go to **Plugins → Add New** in your WordPress admin
2. Search for **"CF7 to API"**
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

## ™️ Trademark Notice & Compliance

### About Contact Form 7 Trademark

**"Contact Form 7"** is a registered trademark of **Rock Lobster, LLC.**, the company behind the development of the Contact Form 7 WordPress plugin.

### Our Compliance

This plugin (**CF7 to API**) is an **independent extension** that adds API integration functionality to Contact Form 7. We comply with the [Contact Form 7 trademark policy](https://contactform7.com/trademark-policy/) as follows:

✅ **Plugin Name**: We use "CF7" (an unofficial abbreviated form permitted by the policy) instead of "Contact Form 7" in our plugin name
✅ **No Affiliation**: We clearly state that this plugin is not affiliated with, endorsed by, or sponsored by Contact Form 7 or Rock Lobster, LLC
✅ **Documentation**: We mention "Contact Form 7" only for reference and compatibility information
✅ **No Confusion**: We avoid any suggestion of official endorsement or partnership

### Our Relationship with Contact Form 7

- **What we are**: An independent extension plugin that enhances Contact Form 7 with API connectivity features
- **What we are NOT**: An official Contact Form 7 product, affiliate, or endorsed extension
- **Our purpose**: To provide additional functionality to Contact Form 7 users who need API integrations

### Acknowledgment

We are grateful to Rock Lobster, LLC and the Contact Form 7 development team for creating and maintaining the excellent Contact Form 7 plugin that makes this extension possible.

If you use Contact Form 7, please consider [supporting its development](https://contactform7.com/donate/).

---

## 📄 License Notice

This project is licensed under the **Polyform Noncommercial License 1.0.0**.

You are free to **use, modify, and share** this code **for noncommercial purposes only**.  
Commercial use of any kind is **not permitted** without explicit permission from the author.  

See the [LICENSE](LICENSE) file for the full license text.

---

**Made with ❤️ by [Silver Assist](https://silverassist.com)**
