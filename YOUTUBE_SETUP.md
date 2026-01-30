# YouTube Stats Auto-Fetch Setup

## Get YouTube API Key:

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable **YouTube Data API v3**:
   - Go to "APIs & Services" > "Library"
   - Search for "YouTube Data API v3"
   - Click "Enable"
4. Create API Key:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "API Key"
   - Copy the API key

## Add to .env:

```
YOUTUBE_API_KEY=your_api_key_here
```

## How it works:

- When mediator submits YouTube link, system automatically fetches views, likes, comments
- Payout is calculated automatically if requirements are met
- Instagram still requires manual verification (API restrictions)
- Admin can manually update counts anytime from admin panel

## API Quota:

- Free tier: 10,000 units/day
- Each video stats request = 1 unit
- Should be sufficient for normal usage
