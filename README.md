# Quick Cart Premium System

This package upgrades Quick Cart into a polished, senior-style commercial web system.

## Included upgrades

- premium sidebar and topbar layout
- polished storefront UI
- secure login and registration
- valid ID upload during registration
- admin verification workflow for uploaded IDs
- checkout disabled until ID verification is approved
- product CRUD
- cart and checkout flow
- live analytics dashboard
- real-time order notifications
- AJAX dashboard filtering without page reload
- reporting with print and CSV export

## Installation

1. Delete any old `quick_cart_final_system` folder from `xampp/htdocs`
2. Extract this ZIP into `xampp/htdocs`
3. Make sure the folder name is exactly:
   `quick_cart_final_system`
4. Start Apache and MySQL in XAMPP
5. Create database `quick_cart`
6. Import `database.sql`
7. Open:
   `http://localhost/quick_cart_final_system`

## Default admin account

- Email: `admin@quickcart.test`
- Password: `admin12345`

## Notes

- Uploaded IDs are stored in `assets/ids/`
- Product images are stored in `assets/images/`
- This is a premium academic prototype suitable for capstone demo and further development.
