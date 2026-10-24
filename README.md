SF Realtor Leads
=================

A full-stack lead management and automation platform
designed for real estate proffesionals.

The system captures leads from external websites, stores and manages
them through a centralized CRM dashboard, and automatically sends follow-up
communications based on configurable workflows.

Demo
-----
SF Realtor Leads (frontend):    https://rodrigopaz-dev.github.io/sfrealtorleads
SF Realtor Leads APP (backend): https://sfrealtorleads.onrender.com

How to use:
SF Realtor Leads is the website for users (realtors) where they can see what the
platform is designed for. It shows the system features and how to get it.

SF Realtor Leads APP is the system or platform for manage leads. You can use this platform
for see how the system works and its features. This is a MVP of the app. Some features or
process may be not include. If you want to sign in, you can use a demo user. For this MVP,
you can use 'user - password' as an user - password respectively.

Key Features
-------------
- Lead capture from external websites
- REST API integration
- Lead management dashboard
- Lead status and lifecycle management
- Automated email responses
- Scheduled follow-up campaigns
- Cron-based background processing
- Realtor dashboard
- Authentication and authorization
- MySQL database
- Responsive web interface
- Integration with third-party websites

## Architecture Decisions

### ADR-001: Separate Frontend, Backend, and Database Hosting

**Context**
The application requires a web interface, server-side PHP processing, and persistent relational storage. The initial release also needs to keep infrastructure and operational complexity manageable.

**Decision**
The application uses GitHub Pages for the static frontend, Render with Docker for the PHP backend, and Aiven for managed MySQL hosting.

**Rationale**
- GitHub Pages provides static frontend hosting without requiring a separate application server.
- Docker packages the PHP runtime and its server dependencies for deployment on Render.
- Managed MySQL reduces the need to operate the database infrastructure directly.

**Trade-offs**
- The application depends on multiple external services.
- Network connectivity and configuration must be managed across service boundaries.
- Deployment and debugging require checking the relevant provider's logs and configuration.
- The application must handle backend or database unavailability gracefully.

**Alternatives Considered**
Other hosting arrangements could consolidate some components or provide different operational capabilities. The current design prioritizes a simple separation of responsibilities for the MVP.

**Status:** Initial architecture implemented; production reliability and recovery behavior require validation.

Architecture
-------------
```text
                      GitHub Pages
           User Interface (HTTPS / API Request)
                           ↓
                 Render - Docker - PHP
         API, authentication and business logic
             ↓                           ↓
    Queries and Writing       Scheduled Background Tasks
             ↓                           ↓
           Aiven                     Follow-up
      MySQL Persistent            Email Proccesing
```
