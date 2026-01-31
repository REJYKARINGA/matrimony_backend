# Social Media Stats Auto-Fetch Setup

## 1. YouTube Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable **YouTube Data API v3**:
   - Go to "APIs & Services" > "Library"
   - Search for "YouTube Data API v3"
   - Click "Enable"
4. Create API Key:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "API Key"
   - Copy the API key
5. Add to `.env`:
   ```
   YOUTUBE_API_KEY=your_api_key_here
   ```

## 2. Instagram Setup (Using your Meta App)

Since you already have **Meta Apps**:

1. Log into [Meta for Developers](https://developers.facebook.com/).
2. Select your App.
3. Use the **Graph API Explorer**:
   - Set the App to yours.
   - Generate a **User Access Token**.
   - Add permissions: `instagram_basic`, `instagram_manage_insights`, `instagram_manage_comments`, `pages_show_list`.
4. Exchange this for a **Long-Lived Access Token** via the Developer portal or CLI.
5. Paste the token into your `.env`:
   ```
   INSTAGRAM_ACCESS_TOKEN=your_meta_app_token_here
   ```

**Pro Tip**: For full stats including views (Reels), make sure your Meta App is in "Live" mode and has the `instagram_manage_insights` permission approved.

**Alternative Implementation (Recommended for easy setup):**
If you find the official API too complex, you can use a scraper service like **RapidAPI (Instagram Scraper)** and update the URL in `SocialMediaStatsService.php`.

## 3. Scheduler

Ensure the scheduler is running to update stats every 5 minutes:
```bash
php artisan schedule:work
```

## How metrics work:
- **YouTube**: Fetches Views, Likes, and Comments automatically.
- **Instagram**: Fetches Likes and Comments (Views are primarily available for Reels via Insights API).
- **Update Frequency**: Every 5 minutes for all pending/verified promotions.
