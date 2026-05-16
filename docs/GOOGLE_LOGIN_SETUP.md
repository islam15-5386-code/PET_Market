# Google Login Setup

This project uses Laravel Socialite on the backend and JWT auth for issuing user tokens after Google OAuth.

## 1) Google Cloud Console

Create OAuth credentials:

1. Go to Google Cloud Console -> APIs & Services -> Credentials.
2. Create an **OAuth Client ID** (Web application).
3. Add these:

### Authorized JavaScript origins

- `http://localhost:3000`
- `http://localhost:8000`

### Authorized redirect URIs

- `http://localhost:8000/api/auth/google/callback`

## 2) Backend env (`src/.env`)

Set:

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
FRONTEND_URL=http://localhost:3000
```

Do not expose `GOOGLE_CLIENT_SECRET` in frontend env variables.

## 3) Frontend env (`frontend/.env.local`)

Set:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

## 4) OAuth flow

1. User clicks **Continue with Google** on login/register page.
2. Frontend opens backend route:
   - `GET /api/auth/google/redirect`
3. Backend redirects to Google consent screen.
4. Google returns to:
   - `GET /api/auth/google/callback`
5. Backend finds/creates/links user and issues JWT token.
6. Backend redirects to frontend:
   - `http://localhost:3000/auth/google/callback?token=<jwt>`
7. Frontend callback page stores token, verifies `/auth/me`, and redirects home.

## 5) User linking behavior

- If same email exists: links `google_id`, keeps existing role, updates avatar if empty, marks `email_verified_at` when missing.
- If user does not exist: creates new `role=user` account with random password and verified email timestamp.
- No duplicate account should be created for same email.

## 6) Local test checklist

1. Start services:
   - backend at `http://localhost:8000`
   - frontend at `http://localhost:3000`
2. Open `/login`.
3. Click **Continue with Google**.
4. Complete Google consent.
5. Confirm redirect to frontend and logged-in navbar/profile state.
6. Call `/api/auth/me` using stored token (automatic in app).
7. Logout and login again with Google; verify same user is reused.

## 7) Common errors

- `redirect_uri_mismatch`:
  - Ensure Google Console redirect URI exactly matches:
    `http://localhost:8000/api/auth/google/callback`
- `Google login is not configured yet`:
  - Missing `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI` in backend env.
- callback returns error to login page:
  - User canceled consent or provider returned invalid data.
- missing email from provider:
  - Use a Google account that shares a valid email.

