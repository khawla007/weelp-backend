# Auth Page Redesign - Design Spec

**Date:** 2026-03-25
**Status:** Approved

## Overview

Combine `/user/login` and `/user/signup` into a single page with tab-based navigation. Create a professional split-screen layout for the auth page while maintaining the existing compact popup functionality from the header.

**Goals:**
- Single entry point for authentication with instant tab switching
- Proper full-page design for direct navigation (`/user/login`)
- Maintain popup functionality from homepage header
- Consistent visual design matching existing design system

---

## Current State Issues

1. **Poor page experience:** Visiting `/user/login` directly shows a small popup-styled card floating in gray void with a non-functional close button
2. **Inconsistent UX:** Desktop uses popup, mobile uses page links, separate URLs for login/signup
3. **Duplicate maintenance:** `AuthModal` component manages state, separate pages exist
4. **Confusing navigation:** Login/signup toggle only works in popup context, not on pages

---

## Proposed Solution

### Page Layout

**Desktop: Split Screen (50/50)**

```
┌─────────────────────────────────────────────────────────┐
│  ┌──────────────────────┐  ┌────────────────────────┐  │
│  │                      │  │                        │  │
│  │   Hero Image         │  │    ┌──────────────┐   │  │
│  │   Travel/Destinations│  │    │  Site Logo   │   │  │
│  │                      │  │    └──────────────┘   │  │
│  │                      │  │                        │  │
│  │   [Optional:         │  │    [Login] [Sign Up]   │  │
│  │    marketing copy,   │  │    ──────────────────   │  │
│  │    trust signals]    │  │                        │  │
│  │                      │  │    Email Field          │  │
│  │                      │  │    Password Field       │  │
│  │                      │  │    Forgot Password?     │  │
│  │                      │  │                        │  │
│  │                      │  │    [Continue]           │  │
│  │                      │  │                        │  │
│  │                      │  │    Don't have account?   │  │
│  │                      │  │    Sign Up link          │  │
│  └──────────────────────┘  └────────────────────────┘  │
│           50%                         50%                │
└─────────────────────────────────────────────────────────┘
```

**Mobile: Stacked Vertical**

```
┌─────────────────────────┐
│  ┌───────────────────┐  │
│  │   Hero Image      │  │  <- 35% height
│  │   (full width)    │  │
│  └───────────────────┘  │
│  ┌───────────────────┐  │
│  │                   │  │
│  │   Auth Card       │  │  <- 65% height
│  │   (centered)      │  │
│  │                   │  │
│  └───────────────────┘  │
└─────────────────────────┘
```

---

### Visual Design

**Colors:**
- Primary brand: `#588f7a` (secondaryDark)
- Primary light: `#b5d8cb` (secondarylight)
- Background: `#F8F9F9`
- Card background: White
- Text primary: `hsl(var(--foreground))`
- Text secondary: `hsl(var(--muted-foreground))`

**Card Styling:**
- Border radius: `rounded-lg` (8px)
- Padding: `p-8` (32px)
- Shadow: `shadow-[0_2.8px_7.2px_rgba(0,0,0,0.05)]`
- Border: `border border-gray-200`

**Typography:**
- Headings: `font-interTight`, font-weight 600
- Body: `font-inter`, font-weight 400
- Labels: `text-sm font-medium text-gray-700`

**Tabs:**
- Active: Green background or underline `bg-[#588f7a] text-white`
- Inactive: Gray text `text-gray-500 hover:text-gray-700`
- Transition: `transition-all duration-200`

**Buttons:**
- Primary: `bg-[#588f7a] hover:bg-[#4a7a63] text-white`
- Full width on mobile, auto width on desktop
- Rounded: `rounded-md`

---

### Routing & Navigation

| URL | Behavior |
|-----|----------|
| `/user/login` | Render page with Login tab active |
| `/user/signup` | Redirect to `/user/login?tab=signup` |
| `/user/login?tab=signup` | Render page with Sign Up tab active |
| `/user/login?tab=login` | Render page with Login tab active (default) |

**Popup (from header):**
- Opens compact Radix Dialog (current behavior)
- Uses existing `AuthModal` pattern with visual refresh
- No changes to popup functionality

---

## Components

### New Component: `AuthPage`

**Location:** `src/app/(frontend)/user/login/page.js`

**Responsibilities:**
- Server Component that wraps the client-side auth content
- Handles redirect for authenticated users
- Reads `tab` URL param to determine default active tab

```jsx
const LoginPage = async ({ searchParams }) => {
  const session = await auth();
  if (session?.user) redirect('/dashboard');

  const defaultTab = searchParams.tab === 'signup' ? 'signup' : 'login';
  return <AuthPageClient defaultTab={defaultTab} />;
};
```

### New Component: `AuthPageClient`

**Location:** `src/app/components/Form/AuthPageClient.jsx`

**Responsibilities:**
- Manages tab state (login | signup)
- Renders split-screen layout
- Shows/hides LoginForm or RegisterForm based on active tab
- Handles tab switching with instant state update

**Props:**
- `defaultTab`: Initial tab ('login' | 'signup')

**State:**
- `activeTab`: Current tab ('login' | 'signup')

### Modified: `LoginForm` / `RegisterForm`

**New prop:** `showCloseButton` (boolean, default: true)

- When `true`: Shows close button (X) for popup usage
- When `false`: Hides close button for page usage

**Current props preserved:**
- `customUrl`: Optional redirect URL after login
- `onSwitchToSignup` / `onSwitchToLogin`: Callbacks for tab switching (popup context)

### Modified: `AuthModal` (Visual refresh only)

**Location:** `src/app/components/Form/AuthModal.jsx`

**Changes:**
- Refresh styling to match design system (proper shadows, border radius, green buttons)
- No functional changes to behavior

---

## File Structure Changes

```
src/app/(frontend)/user/
├── login/
│   └── page.js           # New: AuthPage with tab handling
├── signup/
│   └── page.js           # Modified: Redirect to /user/login?tab=signup
├── layout.js             # Unchanged: Keep existing centered layout
└── page.js              # Remove: No longer needed (redirects to login)

src/app/components/Form/
├── AuthPageClient.jsx    # New: Client-side page component with tabs
├── AuthModal.jsx         # Modified: Visual refresh only
├── LoginForm.jsx         # Modified: Add showCloseButton prop
└── RegisterForm.jsx      # Modified: Add showCloseButton prop
```

---

## Behavior Specifications

### Tab Switching

1. User visits `/user/login` → Login tab is active
2. User clicks "Sign Up" tab → Form instantly switches (no page reload)
3. URL updates to `/user/login?tab=signup` (optional, for shareability)
4. Form validation errors are preserved when switching tabs
5. User can switch back and forth without losing form input

### Authentication Flow

**Login Success:**
- Show success toast
- Redirect based on user role:
  - Admin/Super Admin → `/dashboard/admin`
  - Customer → `/dashboard/customer`
  - With `customUrl` prop → Redirect to custom URL

**Registration Success:**
- Show success toast with message "Registration successful! Please verify your email."
- Switch to Login tab automatically
- Reset form fields

### Popup Behavior (unchanged)

- Desktop header avatar click → Opens popup
- Popup shows login/signup toggle
- Close button (X) closes popup
- Clicking outside closes popup
- Login/signup from popup works identically to page

---

## Mobile Responsive

**Breakpoints:**
- Mobile (< 768px): Stacked layout (hero top 35%, form bottom 65%)
- Tablet (768px - 1024px): Split screen but with narrower margins
- Desktop (> 1024px): Full split screen 50/50

**Mobile Adjustments:**
- Hero image: Full width, 35vh height
- Auth card: Full width minus padding, centered
- Buttons: Full width
- Tabs: Full width with equal sizing

---

## Edge Cases

1. **Direct navigation to /user/login while authenticated**
   - Redirect to `/dashboard` immediately

2. **Direct navigation to /user/signup**
   - Redirect to `/user/login?tab=signup`

3. **URL param with invalid tab value**
   - Default to 'login' tab

4. **Popup open and user navigates to /user/login**
   - Popup closes, page loads with default tab

5. **Form has validation errors when switching tabs**
   - Preserve errors or reset? → Reset for cleaner UX

---

## Success Criteria

- [ ] `/user/login` renders with split-screen layout (desktop)
- [ ] `/user/signup` redirects to `/user/login?tab=signup`
- [ ] Tab switching works instantly without page reload
- [ ] Popup from header still works with login/signup toggle
- [ ] No close button visible on page version
- [ ] Close button visible in popup version
- [ ] Mobile layout stacks vertically
- [ ] Authenticated users redirected to dashboard
- [ ] Visual design matches existing design system
- [ ] No console errors or hydration warnings

---

## Implementation Notes

1. **Preserve existing validation rules** - Password still requires min 8 chars + letter + number + special char
2. **Preserve existing API integration** - No backend changes required
3. **Keep Zod validation** - Use existing schemas
4. **Preserve toast notifications** - Use existing useToast hook
5. **Maintain role-based redirects** - Admin vs customer routing unchanged

---

## Deferred (Future Enhancements)

- Social login (Google, Facebook)
- "Remember me" checkbox
- Email verification reminder on page
- Password strength indicator
- Show/hide password functionality (already exists in LoginForm)
- Marketing copy on left side ( testimonials, trust signals)
