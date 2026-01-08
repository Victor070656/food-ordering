Design and build a simple, professional, and efficient Home-Cooked Meal Delivery Management System tailored for small food vendors and home-based caterers in Nigeria.
The system should solve common operational problems such as order mix-ups, poor delivery coordination, lack of customer records, manual payment reconciliation, and poor post-order communication.

Technology Stack

Backend: Vanilla PHP (OOP where appropriate)

Database: MySQL (mysqli)

Frontend: TailwindCSS (CDN)

JavaScript: Vanilla JS / Alpine.js (optional)

SMS/WhatsApp: API-ready (e.g. Termii, Twilio, WhatsApp Cloud API – abstracted)

Core Modules & Features
1. Authentication & Roles

Admin (Business Owner)

Staff (Kitchen / Order Processing)

Rider (Delivery Personnel)

Each role must have restricted access based on permissions.

2. Order Management System

Centralized Real-time Order Dashboard

Order status flow:

Pending → Preparing → Out for Delivery → Delivered

View orders by:

Today

Pending

In-progress

Completed

Prevent duplicate or lost orders.

3. Customer Database (Mini CRM)

Store:

Full name

Phone number

Address

Order history

Preferences (e.g. spicy, no pepper, extra protein)

Auto-link customers to new orders using phone number.

Quick search by name or phone.

4. Delivery & Rider Management

Create and manage riders.

Assign riders to orders.

Track delivery status:

Assigned

Picked Up

Delivered

Rider login dashboard showing only their assigned deliveries.

5. Payment Tracking

Support:

Bank Transfer

POS

Cash on Delivery

Tag each order as:

Paid

Pending

Failed

Simple reconciliation view for the admin.

6. Automated Notifications (SMS / WhatsApp)

Trigger automatic messages for:

Order confirmation

Order in preparation

Out for delivery

Delivered

Messages should be customizable from the admin panel.

7. Admin Dashboard

Total orders (today, week, month)

Total revenue

Pending deliveries

Pending payments

Top customers

UI/UX Requirements

Clean, modern, professional and fancy look.

Mobile-friendly (important for riders and staff).

Use Tailwind components (cards, badges, tables, modals).

Status badges with colors:

Pending (yellow)

Preparing (blue)

Out for delivery (purple)

Delivered (green)

Failed (red)

Database Requirements

Design normalized tables for:

users

customers

orders

order_items

riders

payments

notifications

Use proper foreign keys and indexes.

Extra Requirements

Simple installation (no frameworks).

Well-structured folder system.

Clean, readable code with comments.

Ready for future features like:

Online ordering page

Mobile app integration

POS integration

End Goal

A special but simple, production-ready system that:

Eliminates order confusion

Improves delivery coordination

Tracks customers and payments accurately

Improves customer communication

Works smoothly for small Nigerian food businesses