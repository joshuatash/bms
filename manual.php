<?php require __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Manual | Big Wallet Lounge</title>
    <style>
        :root{
            --bg:#0b1220;
            --surface:#111c2e;
            --surface-2:#16243a;
            --surface-3:#1e2d47;
            --text:#e2e8f0;
            --text-dim:#94a3b8;
            --text-mute:#64748b;
            --border:#243349;
            --amber:#f59e0b;
            --green:#16a34a;
            --red:#dc2626;
            --blue:#2563eb;
        }
        *{box-sizing:border-box}
        html{scroll-behavior:smooth;scroll-padding-top:20px}
        body{margin:0;font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.65;font-size:15px}

        /* Layout */
        .layout{display:grid;grid-template-columns:280px 1fr;gap:0;min-height:100vh}
        .sidebar{
            background:var(--surface);
            border-right:1px solid var(--border);
            padding:24px 20px;
            position:sticky;top:0;
            height:100vh;
            overflow-y:auto;
        }
        .sidebar h2{
            margin:0 0 6px;font-size:1.05rem;color:var(--amber);
            text-transform:uppercase;letter-spacing:1px;font-weight:800;
        }
        .sidebar p{margin:0 0 16px;color:var(--text-mute);font-size:.8rem}
        .pdf-btn{
            display:flex;align-items:center;justify-content:center;gap:8px;
            width:100%;padding:11px 16px;margin-bottom:20px;
            background:var(--amber);color:#0b1220;
            border:0;border-radius:8px;
            font-weight:800;font-size:.88rem;font-family:inherit;
            cursor:pointer;transition:.2s;
            box-shadow:0 4px 6px -1px rgba(245,158,11,.3);
        }
        .pdf-btn:hover{background:#d97706;color:#fff;transform:translateY(-1px)}
        .pdf-btn:active{transform:translateY(0)}
        .toc{list-style:none;margin:0;padding:0}
        .toc li{margin-bottom:2px}
        .toc a{
            display:block;padding:8px 12px;border-radius:8px;
            color:var(--text-dim);text-decoration:none;
            font-size:.85rem;font-weight:600;
            transition:background .15s,color .15s;
            border-left:3px solid transparent;
        }
        .toc a:hover{background:var(--surface-2);color:var(--text);border-left-color:var(--amber)}
        .toc a.active{background:var(--surface-2);color:var(--amber);border-left-color:var(--amber)}

        .content{padding:32px 48px 80px;max-width:1000px}
        .content h1{margin:0 0 8px;font-size:2rem;color:var(--text);letter-spacing:-.3px;line-height:1.2}
        .content > p.subtitle{margin:0 0 40px;color:var(--text-dim);font-size:1rem;line-height:1.6}
        .content h2{
            margin:48px 0 14px;font-size:1.4rem;color:var(--text);
            padding-top:20px;border-top:1px solid var(--border);
        }
        .content h2:first-of-type{border-top:none;padding-top:0;margin-top:24px}
        .content h3{margin:28px 0 10px;font-size:1.08rem;color:var(--amber);font-weight:700}
        .content h4{margin:20px 0 8px;font-size:.95rem;color:var(--text);font-weight:700}
        .content p{margin:10px 0;color:var(--text-dim)}
        .content p strong,.content p b{color:var(--text)}
        .content ul,.content ol{margin:10px 0;padding-left:24px;color:var(--text-dim)}
        .content li{margin:5px 0}
        .content li strong,.content li b{color:var(--text)}
        .content code{
            background:var(--surface-2);padding:2px 7px;border-radius:5px;
            color:var(--amber);font-family:'Courier New',monospace;font-size:.88em;
        }
        .content a{color:var(--amber);text-decoration:none;font-weight:600}
        .content a:hover{text-decoration:underline}

        /* Callout boxes */
        .callout{
            background:var(--surface-2);border-left:4px solid var(--amber);
            border-radius:8px;padding:14px 18px;margin:16px 0;
            color:var(--text-dim);font-size:.92rem;
        }
        .callout strong{color:var(--text)}
        .callout.danger{border-left-color:var(--red);background:rgba(220,38,38,.08)}
        .callout.danger strong{color:#f87171}
        .callout.success{border-left-color:var(--green);background:rgba(22,163,74,.08)}
        .callout.success strong{color:#4ade80}
        .callout.info{border-left-color:var(--blue);background:rgba(37,99,235,.08)}
        .callout.info strong{color:#60a5fa}

        /* Tables */
        .content table{
            width:100%;border-collapse:collapse;margin:16px 0;
            background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;
            font-size:.9rem;
        }
        .content th{
            text-align:left;background:var(--surface-2);color:var(--text);
            padding:12px 14px;font-size:.75rem;text-transform:uppercase;
            letter-spacing:.5px;border-bottom:1px solid var(--border);
            font-weight:800;
        }
        .content td{
            padding:12px 14px;border-bottom:1px solid var(--border);
            color:var(--text-dim);vertical-align:top;
        }
        .content tr:last-child td{border-bottom:none}
        .content td strong{color:var(--text)}

        /* Step lists */
        .steps{list-style:none;padding:0;counter-reset:step;margin:16px 0}
        .steps li{
            counter-increment:step;position:relative;
            padding:10px 14px 10px 48px;margin:8px 0;
            background:var(--surface-2);border-radius:8px;
            border:1px solid var(--border);color:var(--text-dim);
        }
        .steps li::before{
            content:counter(step);position:absolute;left:14px;top:50%;
            transform:translateY(-50%);
            width:24px;height:24px;border-radius:50%;
            background:var(--amber);color:#0b1220;
            display:grid;place-items:center;font-weight:800;font-size:.8rem;
        }
        .steps li strong{color:var(--text)}

        /* Footer */
        .manual-footer{
            margin-top:60px;padding-top:24px;border-top:1px solid var(--border);
            color:var(--text-mute);font-size:.85rem;line-height:1.7;
        }
        .manual-footer a{color:var(--amber);text-decoration:none}
        .manual-footer a:hover{text-decoration:underline}

        /* Back to top */
        .back-top{
            position:fixed;right:20px;bottom:20px;z-index:60;
            width:52px;height:52px;border-radius:50%;border:0;
            background:var(--green);color:#fff;font-size:1.4rem;font-weight:900;
            cursor:pointer;display:grid;place-items:center;
            box-shadow:0 8px 20px rgba(22,163,74,.45);
            opacity:0;visibility:hidden;transform:translateY(12px);
            transition:opacity .25s ease,transform .25s ease,background .2s ease;
        }
        .back-top.show{opacity:1;visibility:visible;transform:translateY(0)}
        .back-top:hover{background:#15803d}
        .back-top:active{transform:translateY(1px) scale(.96)}
        .back-top .tip{
            position:absolute;right:calc(100% + 10px);top:50%;transform:translateY(-50%);
            background:#052e16;color:#bbf7d0;font-size:.78rem;font-weight:700;
            padding:6px 10px;border-radius:8px;white-space:nowrap;
            opacity:0;pointer-events:none;transition:opacity .2s ease;border:1px solid #166534;
        }
        .back-top:hover .tip,.back-top:focus-visible .tip{opacity:1}
        .back-top svg{width:22px;height:22px}

        /* Mobile */
        @media(max-width:900px){
            .layout{grid-template-columns:1fr}
            .sidebar{position:relative;height:auto;border-right:none;border-bottom:1px solid var(--border)}
            .toc{display:grid;grid-template-columns:repeat(2,1fr);gap:4px}
            .content{padding:24px 20px 60px}
            .content h1{font-size:1.5rem}
            .content h2{font-size:1.2rem}
            .content h3{font-size:1rem}
            .content table{font-size:.83rem}
            .content th,.content td{padding:8px 10px}
            .back-top{bottom:16px;right:14px}
            .back-top .tip{display:none}
        }
        @media(max-width:500px){
            .toc{grid-template-columns:1fr}
            .content{padding:18px 14px 40px}
        }

        /* Print */
        @media print {
            body{background:#fff;color:#0f172a;font-size:11pt}
            .layout{display:block}
            .sidebar{display:none}
            .back-top{display:none !important}
            .content{padding:0;max-width:none}
            .content h1{font-size:20pt}
            .content h2{font-size:14pt;border-top:1px solid #cbd5e1;page-break-after:avoid}
            .content h3{font-size:11.5pt;color:#b45309;page-break-after:avoid}
            .content p,.content li,.content td{color:#334155}
            .content p strong,.content li strong,.content td strong{color:#0f172a}
            .content table{border:1px solid #cbd5e1;background:#fff}
            .content th{background:#0f172a;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;border-bottom:none}
            .content td{border-bottom:1px solid #e2e8f0}
            .callout{background:#f8fafc;border:1px solid #cbd5e1;border-left:4px solid #f59e0b;color:#334155}
            .callout strong{color:#0f172a}
            .callout.danger{border-left-color:#dc2626;background:#fef2f2}
            .callout.success{border-left-color:#16a34a;background:#f0fdf4}
            .callout.info{border-left-color:#2563eb;background:#eff6ff}
            .steps li{background:#f8fafc;border:1px solid #e2e8f0;color:#334155}
            .steps li strong{color:#0f172a}
            .steps li::before{background:#f59e0b;color:#0f172a}
            code{background:#f1f5f9;color:#b45309}
            .manual-footer{color:#64748b}
            @page{size:A4;margin:15mm}
            a{color:#b45309}
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <h2>User Manual</h2>
        <p>Big Wallet Lounge Management System</p>

        <button type="button" class="pdf-btn" id="downloadPdf">Download PDF</button>

        <ul class="toc">
            <li><a href="#getting-started">1. Getting Started</a></li>
            <li><a href="#header">2. The Header</a></li>
            <li><a href="#pos">3. Point of Sale</a></li>
            <li><a href="#products">4. Products</a></li>
            <li><a href="#book-room">5. Book Room</a></li>
            <li><a href="#manage-rooms">6. Manage Rooms</a></li>
            <li><a href="#ground-hire">7. Ground Hire</a></li>
            <li><a href="#pa-system">8. PA System</a></li>
            <li><a href="#expenses">9. Expenses</a></li>
            <li><a href="#reports">10. Reports & Analytics</a></li>
            <li><a href="#activity">11. Activity & Sessions</a></li>
            <li><a href="#hr-dashboard">12. HR Dashboard</a></li>
            <li><a href="#settings">13. System Settings</a></li>
            <li><a href="#landing">14. Landing & Privacy</a></li>
            <li><a href="#tips">15. General Tips</a></li>
        </ul>
    </aside>

    <main class="content">
        <h1>Big Wallet Lounge — User Manual</h1>
        <p class="subtitle">A complete guide to using the Big Wallet Lounge Management System. Written for staff, managers, HR, and administrators.</p>

        <!-- 1 -->
        <h2 id="getting-started">1. Getting Started</h2>

        <h3>Logging In</h3>
        <ol class="steps">
            <li>Open the app and click <strong>Staff Login</strong> (or go directly to the login page).</li>
            <li>Enter your <strong>username</strong> and <strong>password</strong>.</li>
            <li>Click <strong>Sign In</strong>. You will be taken to the dashboard that matches your role.</li>
        </ol>
        <p>The password field has an <strong>eye icon</strong> you can tap to reveal what you've typed — useful on phones.</p>

        <h3>Roles</h3>
        <p>The system has four roles, each with different access:</p>
        <table>
            <thead><tr><th>Role</th><th>What they can do</th></tr></thead>
            <tbody>
                <tr><td><strong>Cashier</strong></td><td>Use the Point of Sale to record sales</td></tr>
                <tr><td><strong>Manager</strong></td><td>Everything a cashier can do, plus products, rooms, ground hire, PA system, expenses, reports, and activity logs (but not user accounts or system resets)</td></tr>
                <tr><td><strong>HR</strong></td><td>User account management, database backup, activity monitoring, and own-password changes</td></tr>
                <tr><td><strong>Admin (CEO)</strong></td><td>Full access — everything above, plus system reset and admin settings</td></tr>
            </tbody>
        </table>
        <p>If you log in and land on a page you didn't expect, it's because your role doesn't have access to the section you were trying to reach. The system redirects automatically.</p>

        <h3>Creating an Account</h3>
        <p>New staff can register themselves from the login page:</p>
        <ol class="steps">
            <li>Click <strong>Create an account</strong>.</li>
            <li>Fill in your full name, username, password, and confirm password.</li>
            <li>Click <strong>Register</strong>.</li>
        </ol>
        <div class="callout"><strong>Note:</strong> Your account is not active immediately. HR or Admin must approve and enable it before you can log in. Until then, signing in will show a message that your account is not yet active.</div>

        <h3>Forgot Password</h3>
        <ol class="steps">
            <li>Click <strong>Forgot Password?</strong> on the login page.</li>
            <li>Enter your username, current password, new password, and confirm the new password.</li>
            <li>Submit the request.</li>
        </ol>
        <p>Your new password is stored as a <strong>pending request</strong>. HR or Admin will approve or reject it from the HR Dashboard. Until approval, you cannot log in with the new password. If you've already forgotten your current password, ask HR to reset it for you directly.</p>

        <h3>Being Signed Out</h3>
        <p>If HR clicks <strong>Force Logout</strong> on your active session, you'll be signed out immediately. Simply sign in again.</p>

        <!-- 2 -->
        <h2 id="header">2. The Header (Common to Every Page)</h2>
        <p>Every internal page shows the same top header. From here you can:</p>
        <ul>
            <li>See your name and role</li>
            <li>Jump between modules your role has access to</li>
            <li>Sign out</li>
        </ul>
        <p>On phones, the header collapses into a compact menu.</p>

        <!-- 3 -->
        <h2 id="pos">3. Point of Sale (POS) — Cashiers, Managers, Admin</h2>
        <p>The POS is where every drink sale is recorded.</p>

        <h3>The Layout</h3>
        <ul>
            <li><strong>Left side</strong> — the product list with a search bar</li>
            <li><strong>Right side</strong> — the current sale (cart) with a running total and the <strong>Complete Payment</strong> button</li>
            <li>On phones, the cart becomes a floating bar at the bottom that you tap to open</li>
        </ul>

        <h3>Selling a Drink</h3>
        <ol class="steps">
            <li><strong>Search</strong> for the drink by name or category (or scroll the list).</li>
            <li>Each product row shows its price, current stock, and status:
                <ul>
                    <li><strong>Available</strong> — enough stock to sell</li>
                    <li><strong>Low Stock</strong> — few left, but still sellable</li>
                    <li><strong>Out of Stock</strong> — cannot be sold (the checkbox is disabled)</li>
                </ul>
            </li>
            <li><strong>Tick the checkbox</strong> next to the drink to add it to the cart. A quantity box appears, pre-set to 1.</li>
            <li><strong>Adjust the bottle count</strong> if the customer wants more than one. The system won't let you exceed available stock.</li>
            <li>The <strong>cart panel</strong> on the right updates instantly: item name, quantity × price, line total, and the grand total at the bottom.</li>
            <li>Repeat for any other drinks the customer is buying.</li>
            <li>Click <strong>Complete Payment</strong>.</li>
        </ol>

        <h3>What Happens When You Complete Payment</h3>
        <ul>
            <li>The system records the sale with a receipt number.</li>
            <li>Stock levels are reduced automatically.</li>
            <li>A success popup appears with the receipt number, total, and two buttons:
                <ul>
                    <li><strong>Print Receipt</strong> — opens a printer-friendly receipt in a new window (auto-triggers your browser's print dialog)</li>
                    <li><strong>New Sale</strong> — clears the cart and returns you to the product list, ready for the next customer</li>
                </ul>
            </li>
        </ul>

        <h3>Managing the Cart</h3>
        <ul>
            <li><strong>Remove an item</strong> — click the red <strong>×</strong> next to it in the cart</li>
            <li><strong>Change quantity</strong> — edit the bottle count in the product row (the cart updates live)</li>
            <li><strong>Clear the entire cart</strong> — click <strong>Clear Cart</strong></li>
        </ul>

        <h3>The Quick Filter Buttons</h3>
        <p>Above the product list are four pill buttons with live counts:</p>
        <ul>
            <li><strong>All</strong> — everything in the catalogue</li>
            <li><strong>Available</strong> — products with healthy stock</li>
            <li><strong>Low Stock</strong> — products that need restocking soon</li>
            <li><strong>Out of Stock</strong> — products that can't be sold</li>
        </ul>
        <p>Tap any of them to filter the list instantly. The counts update as stock changes.</p>

        <h3>The Back-to-Top Arrow</h3>
        <p>If you scroll far down the product list, a green <strong>↑ arrow</strong> appears in the corner. Tap it (or hover for the "Move Up" tooltip) to jump back to the top.</p>

        <h3>Receipt Format</h3>
        <p>Every printed receipt includes:</p>
        <ul>
            <li>The lounge name and address</li>
            <li>Contact details (WhatsApp, phone, email)</li>
            <li>The logo</li>
            <li>Receipt number, date, cashier name</li>
            <li>Every item sold, with quantity × price and line total</li>
            <li>Grand total</li>
            <li>A thank-you message</li>
        </ul>

        <!-- 4 -->
        <h2 id="products">4. Products — Managers, Admin</h2>
        <p>Everything the lounge sells from the bar lives here.</p>

        <h3>Viewing Products</h3>
        <ul>
            <li>The top of the page shows three stat tiles: <strong>Total Products</strong>, <strong>Low Stock</strong>, <strong>Out of Stock</strong>. Click any of them to filter the list.</li>
            <li>Below is the filter toolbar with four count chips: Total, Available, Low, Out.</li>
            <li><strong>Search</strong> by product name.</li>
            <li><strong>Filter by category</strong> using the dropdown.</li>
            <li><strong>Filter by status</strong> (available, low, out) using the second dropdown.</li>
        </ul>

        <h3>Adding a New Product (or Restocking)</h3>
        <ol class="steps">
            <li>Scroll to the <strong>Add Product / Restock</strong> card.</li>
            <li>Start typing the product name — a dropdown of matching existing products appears as you type. <strong>Tap a suggestion</strong> to select an existing product (which will have its details pre-filled) or <strong>ignore the suggestions</strong> if you're adding something brand new.</li>
            <li>Fill in:
                <ul>
                    <li><strong>Category</strong> — choose from the list</li>
                    <li><strong>Buying Price (per bottle)</strong> — what you paid the supplier</li>
                    <li><strong>Selling Price (per bottle)</strong> — what the customer pays</li>
                    <li><strong>Number of Bottles</strong> — how many you're adding</li>
                    <li><strong>Low Stock Alert Level</strong> — when stock drops to or below this number, the product is flagged as "Low"</li>
                </ul>
            </li>
            <li>As you type, a <strong>live preview</strong> at the bottom shows:
                <ul>
                    <li>Whether you're adding a new product or restocking an existing one</li>
                    <li>The profit per bottle (selling price minus buying price), plus a warning if you're selling below cost</li>
                </ul>
            </li>
            <li>Click <strong>Save Stock</strong>.</li>
        </ol>
        <div class="callout info"><strong>Important:</strong> Restocking an existing product adds to the current stock. It does not replace it.</div>

        <h3>Editing a Product</h3>
        <ol class="steps">
            <li>Click <strong>Edit</strong> on any product row.</li>
            <li>Change any field — name, category, buying price, selling price, stock, low-stock threshold.</li>
            <li>Click <strong>Save Changes</strong>.</li>
        </ol>

        <h3>Deleting a Product</h3>
        <ol class="steps">
            <li>Click <strong>Delete</strong> on any product row.</li>
            <li>Confirm the deletion.</li>
        </ol>
        <div class="callout danger"><strong>Note:</strong> Deleting a product removes it from the catalogue, but past sales remain in the records. So historical reports stay accurate.</div>

        <!-- 5 -->
        <h2 id="book-room">5. Book Room — Cashiers, Managers, Admin</h2>
        <p>Used at the reception desk to check guests into rooms.</p>

        <h3>The Layout</h3>
        <ul>
            <li><strong>Available Rooms</strong> — grid of rooms you can book now</li>
            <li><strong>Occupied Rooms</strong> — table of guests currently checked in</li>
            <li><strong>Under Maintenance</strong> — rooms temporarily out of service</li>
            <li><strong>Book a Room</strong> — the booking form (on the right for desktop, on top for mobile)</li>
        </ul>

        <h3>Booking a Room</h3>
        <ol class="steps">
            <li>In the <strong>Available Rooms</strong> grid, tap the room you want. The booking form fills with that room's rate info.</li>
            <li>Fill in:
                <ul>
                    <li><strong>Guest Name</strong></li>
                    <li><strong>Phone</strong> (optional)</li>
                    <li><strong>ID / Passport Number</strong> (optional, but useful)</li>
                    <li><strong>Booking Type</strong> — <strong>Short</strong> (one flat rate, no nights) or <strong>Long</strong> (nightly rate × number of nights)</li>
                    <li><strong>Number of Guests</strong></li>
                    <li><strong>Nights</strong> (only appears for Long stay)</li>
                    <li><strong>Payment Method</strong> — Cash, Mobile Money, or Card</li>
                    <li><strong>Amount Paid</strong> — pre-fills with the full total; edit it if the guest is only paying a deposit</li>
                </ul>
            </li>
            <li>As you type, a <strong>balance preview</strong> at the bottom shows what the guest still owes after this payment.</li>
            <li>Click <strong>Book Room</strong>.</li>
            <li>A success popup appears with:
                <ul>
                    <li>The booking number</li>
                    <li>The total</li>
                    <li>A note about any remaining balance</li>
                    <li>A <strong>Print Receipt</strong> button that opens a paper-style receipt</li>
                </ul>
            </li>
            <li>Click <strong>Done</strong> to close.</li>
        </ol>

        <h3>Viewing Occupied Rooms</h3>
        <p>The <strong>Occupied Rooms</strong> table shows for each room:</p>
        <ul>
            <li>Room number and booking type</li>
            <li>Guest name and phone</li>
            <li>Check-in date</li>
            <li>Due-out date (an <strong>Overdue</strong> tag appears if the due-out date has passed)</li>
            <li>Total, paid, and remaining balance</li>
        </ul>

        <h3>Receiving a Balance Payment</h3>
        <ol class="steps">
            <li>Click <strong>Receive Payment</strong> on their row.</li>
            <li>Enter the amount received (default is the full balance).</li>
            <li>Choose the payment method.</li>
            <li>Click <strong>Save Payment</strong>.</li>
        </ol>
        <p>The balance updates immediately.</p>

        <h3>Checking Out a Guest</h3>
        <ol class="steps">
            <li>Click <strong>Check Out</strong> on the guest's row.</li>
            <li>The system marks the booking complete and the room becomes available again.</li>
        </ol>
        <p>If the guest still has a balance, the system will warn you first. You can receive the payment before checking out, or check out anyway.</p>

        <h3>Viewing a Receipt Again</h3>
        <p>Click the <strong>Receipt</strong> link on any occupied room row to open its receipt in a new tab.</p>

        <h3>Managing Maintenance</h3>
        <p>Rooms under maintenance show in the <strong>Under Maintenance</strong> card:</p>
        <ul>
            <li><strong>Set Maintenance</strong> — click on any available room to mark it out of service (you can add a note like "Air conditioner broken")</li>
            <li><strong>Mark Ready</strong> — click on any maintenance room to make it available again</li>
        </ul>

        <!-- 6 -->
        <h2 id="manage-rooms">6. Manage Rooms / Add Room — Managers, Admin</h2>

        <h3>Add Room</h3>
        <p>Used to create a new room in the system:</p>
        <ol class="steps">
            <li>Enter the <strong>Room Number</strong> (like <code>101</code> or <code>A1</code>)</li>
            <li>Enter the <strong>Room Type</strong> (like <code>SINGLE</code>, <code>DOUBLE</code>, <code>VIP</code>) — the dropdown auto-suggests types already used by other rooms</li>
            <li>Set <strong>Sleeps</strong> (max guests)</li>
            <li>Set <strong>Short Stay Price</strong> and <strong>Long Stay Price</strong></li>
            <li>Optionally add a <strong>Description</strong> (like "Self-contained, TV, first floor")</li>
            <li>Click <strong>Save Room</strong></li>
        </ol>
        <p>The room appears immediately in the "Rooms So Far" table below.</p>

        <h3>Manage Rooms</h3>
        <p>The same info in a table view with:</p>
        <ul>
            <li>Status filter (all, available, occupied, maintenance)</li>
            <li>Search by room number or guest name</li>
            <li><strong>Edit</strong> any room</li>
            <li><strong>Set Maintenance</strong> for available rooms</li>
            <li><strong>Mark Available</strong> for rooms under maintenance</li>
            <li><strong>Delete</strong> (only possible when the room is not occupied)</li>
        </ul>

        <!-- 7 -->
        <h2 id="ground-hire">7. Ground Hire — Managers, Admin</h2>
        <p>For customers hiring the grounds for weddings, parties, and corporate events.</p>

        <h3>Recording a New Booking</h3>
        <ol class="steps">
            <li><strong>Customer / Event Name</strong> — who's booking and for what</li>
            <li><strong>Total Fee</strong> — the agreed price for the ground hire</li>
            <li><strong>Deposit Paid</strong> — how much they've paid today (leave empty if paying in full)</li>
            <li><strong>Method</strong> — Cash, Mobile Money, or Card</li>
            <li><strong>Start Date and Start Time</strong> — when the event starts</li>
            <li><strong>End Date and End Time</strong> — when it ends</li>
            <li><strong>Details / Grounds Taken</strong> — free text describing what's included (e.g. "Main ground, 100 chairs, 2 tents")</li>
            <li>As you type the fee and deposit, a <strong>balance preview</strong> shows what's still owed.</li>
        </ol>
        <p>Click <strong>Save &amp; Print Receipt</strong>.</p>

        <h3>The Success Popup</h3>
        <ul>
            <li>Shows the customer and total</li>
            <li>Shows the pending balance (if any)</li>
            <li><strong>Print Receipt</strong> — opens a paper-style receipt in a new window</li>
            <li><strong>Done</strong> — closes the popup</li>
        </ul>

        <h3>Recent Bookings Table</h3>
        <p>Every booking shows:</p>
        <ul>
            <li><strong>Date recorded</strong></li>
            <li><strong>Interval</strong> — start to end</li>
            <li><strong>Customer</strong></li>
            <li><strong>Details</strong></li>
            <li><strong>Total, Deposit, Balance</strong></li>
            <li><strong>Status</strong> — <strong>Paid</strong> (fully settled) or <strong>Balance: X</strong> (still owes)</li>
            <li><strong>Actions</strong> — <strong>Print</strong> (opens receipt) and, if there's a balance, <strong>Pay Bal</strong> (opens a payment dialog)</li>
        </ul>

        <h3>Collecting a Balance Payment</h3>
        <ol class="steps">
            <li>Click <strong>Pay Bal</strong> next to any booking with an outstanding balance.</li>
            <li>Confirm the amount (default is the full balance — you can enter less for partial payments).</li>
            <li>Choose the payment method.</li>
            <li>Click <strong>Confirm Payment</strong>.</li>
        </ol>
        <p>The booking updates immediately — if the balance reaches zero, its status becomes <strong>Paid</strong>.</p>

        <h3>Deposit Statistics</h3>
        <p>At the top of the page, two tiles track deposits collected:</p>
        <ul>
            <li><strong>Today</strong></li>
            <li><strong>This Month</strong></li>
        </ul>

        <!-- 8 -->
        <h2 id="pa-system">8. PA System — Managers, Admin</h2>
        <p>For hiring out the public address system and sound equipment.</p>
        <p>The PA System module works <strong>exactly like Ground Hire</strong>, with these differences:</p>
        <ul>
            <li>Field is <strong>Customer Name</strong> (no event name)</li>
            <li>Details field is <strong>Items Taken / Details</strong> (e.g. "2 speakers, 2 mics, mixer")</li>
            <li>The receipt title says <strong>PA System Hire Receipt</strong></li>
            <li>It has its own separate deposit statistics</li>
        </ul>
        <p>Everything else — recording, printing, receiving balance payments — works identically.</p>

        <!-- 9 -->
        <h2 id="expenses">9. Expenses — Cashiers, Managers, Admin</h2>
        <p>For tracking money the business spends.</p>

        <h3>Recording an Expense</h3>
        <p>Any logged-in staff (except Admin and Manager) can record expenses. This lets cashiers record small expenses on the spot.</p>
        <ol class="steps">
            <li><strong>Expense Title</strong> — like "Electricity Bill" or "Cleaning Supplies"</li>
            <li><strong>Department</strong> — which part of the business spent the money (Administration, Bar &amp; Beverage, Kitchen, Lodge / Rooms, Ground Hire, PA System, Maintenance)</li>
            <li><strong>Category</strong> — Utilities, Supplies, Maintenance, Transport, Salary, or General</li>
            <li><strong>Amount</strong></li>
            <li><strong>Description</strong> (optional)</li>
            <li>Click <strong>Save Expense</strong></li>
        </ol>

        <h3>Viewing the Expense Report</h3>
        <p>Managers and Admin see a full expense report below the recording form:</p>
        <ul>
            <li><strong>Today / This Week / This Month / All Time</strong> tabs at the top</li>
            <li>Full table of every expense with time, department, category, description, who recorded it, and amount</li>
            <li><strong>Total expenses</strong> at the bottom in red</li>
            <li><strong>Export to PDF</strong> button to print or save the current view</li>
        </ul>

        <!-- 10 -->
        <h2 id="reports">10. Reports &amp; Analytics</h2>

        <h3>Sales Report — Managers, Admin</h3>
        <p>Accessible from the dashboard tiles. Shows:</p>
        <ul>
            <li><strong>Period toggle</strong> — Today or This Month</li>
            <li><strong>Stat tiles</strong> — Total Sales, Gross Profit, Total Expenses, Net Profit, Receipts, Bottles Sold</li>
            <li><strong>By-item breakdown</strong> — for each product: bottles sold, amount, profit, share % (with a bar chart)</li>
            <li><strong>All items sold</strong> — the line-by-line transaction history with time, receipt, item, bottles, price, amount, profit, and cashier</li>
        </ul>
        <p>Click <strong>Export to PDF</strong> to save a copy.</p>

        <h3>Rooms Collection — Managers, Admin</h3>
        <p>Shows all room payments for today or this month:</p>
        <ul>
            <li><strong>Total collected</strong> across all rooms</li>
            <li><strong>Breakdown by payment method</strong> (Cash, Mobile Money, Card)</li>
            <li>Full payment table with room number, guest, booking, type, method, received-by, amount</li>
        </ul>

        <h3>Analytics — Managers, Admin</h3>
        <p>Visual charts and comparisons:</p>
        <ul>
            <li><strong>Per Week</strong> or <strong>Per Month</strong> toggle</li>
            <li><strong>Chart 1 — Gross Profit vs. Expenses</strong> — a donut chart showing how sales profit compares to operating expenses, with net profit in the center</li>
            <li><strong>Chart 2 — Top Selling Products</strong> — a donut chart showing the top five products by revenue</li>
            <li><strong>Chart 3 — Revenue Streams</strong> — a donut showing how Bar Sales, Rooms, Ground Hire, and PA System compare as income sources</li>
        </ul>
        <p>Each chart includes a short plain-English interpretation.</p>

        <!-- 11 -->
        <h2 id="activity">11. Activity &amp; Sessions — Managers, Admin, HR</h2>
        <p>Every action in the system is logged. This module shows who did what, when, and where from.</p>

        <h3>Two Views</h3>
        <ul>
            <li><strong>Login Sessions</strong> — every time someone signed in, how long they stayed, whether they logged out, their device, and their location</li>
            <li><strong>Activity Log</strong> — every action (sales, product edits, room bookings, password changes, resets, etc.) with a detailed description</li>
        </ul>

        <h3>Filters</h3>
        <p>Both views support filtering by:</p>
        <ul>
            <li><strong>User</strong> — see only one person's activity</li>
            <li><strong>Date range</strong> — from and to dates</li>
            <li><strong>Action type</strong> (activity view only)</li>
            <li><strong>Search text</strong> — matches against the details (activity view only)</li>
        </ul>

        <h3>Login Status Indicators</h3>
        <ul>
            <li><strong>Active</strong> — signed in and last active within 15 minutes</li>
            <li><strong>Logged out</strong> — properly signed out</li>
            <li><strong>Ended without logout</strong> — session ended abruptly (browser closed, network lost)</li>
        </ul>

        <h3>Failed Login Details</h3>
        <p>The HR Dashboard highlights failed login attempts with the reason recorded:</p>
        <ul>
            <li>"Unknown username"</li>
            <li>"Wrong password"</li>
            <li>"Account is disabled"</li>
            <li>Plus the location they attempted from</li>
        </ul>

        <h3>Clearing the Log (Admin only)</h3>
        <p>Admin can clear the log from the Activity module:</p>
        <ul>
            <li><strong>What to clear</strong> — activity log, login sessions, or both</li>
            <li><strong>Which records</strong> — everything, or only records older than 30 or 90 days</li>
            <li><strong>Password confirmation</strong> — required</li>
        </ul>
        <p>People who are signed in right now are always kept. The reset itself is recorded in the log.</p>

        <h3>Export</h3>
        <p>Click <strong>Export to PDF</strong> to save the current view as a printable report.</p>

        <!-- 12 -->
        <h2 id="hr-dashboard">12. HR &amp; Security Dashboard — HR only</h2>
        <p>The central place to manage accounts and monitor the system.</p>

        <h3>User Management</h3>
        <ul>
            <li>See all accounts with name, role, and status (Active / Disabled)</li>
            <li><strong>Create Account</strong> — with name, username, role, and password</li>
            <li><strong>Disable / Enable</strong> any account</li>
            <li><strong>Delete</strong> any account (except your own)</li>
            <li><strong>Approve or Reject</strong> pending password reset requests (shown with a warning badge)</li>
        </ul>
        <p>You cannot disable or delete your own account.</p>

        <h3>Database Backup</h3>
        <p>Click <strong>Download Database Backup (.sql)</strong> to save a complete snapshot of the entire database as an SQL file. Store these somewhere safe — it's your recovery option if something goes wrong.</p>
        <p>Every backup download is logged.</p>

        <h3>Active Sessions</h3>
        <p>Live view of who's logged in right now:</p>
        <ul>
            <li>Username, IP address, and location (approximate country/city)</li>
            <li>Device and browser</li>
            <li>Login time and last activity time</li>
            <li><strong>Force Logout</strong> — signs that user out immediately</li>
        </ul>

        <h3>Change My Password</h3>
        <p>Update your own HR password:</p>
        <ol class="steps">
            <li>Enter your <strong>current password</strong></li>
            <li>Enter and confirm your <strong>new password</strong> (at least 8 characters)</li>
            <li>Click <strong>Update Password</strong></li>
        </ol>
        <p>The change is recorded in the activity log.</p>

        <h3>Recent Activity Timeline</h3>
        <p>A compact view of the last 50 activity records, shown as a timeline with:</p>
        <ul>
            <li>Who did it</li>
            <li>What they did</li>
            <li>When and from where</li>
            <li><strong>Clear Logs</strong> button (with confirmation) to wipe the entire activity log</li>
        </ul>

        <!-- 13 -->
        <h2 id="settings">13. System Settings — Admin only</h2>
        <p>The Danger Zone for resetting system data.</p>

        <h3>What It Does</h3>
        <p>Admin can permanently delete different categories of records without affecting user accounts or (crucially) the product catalogue:</p>
        <table>
            <thead><tr><th>Reset scope</th><th>What gets cleared</th><th>What stays</th></tr></thead>
            <tbody>
                <tr><td><strong>Sales</strong></td><td>All sales and sale items</td><td>Products, users</td></tr>
                <tr><td><strong>Inventory</strong></td><td>Stock entries only</td><td>Products, users, sales</td></tr>
                <tr><td><strong>Rooms</strong></td><td>Rooms, bookings, and payments</td><td>Users</td></tr>
                <tr><td><strong>Expenses</strong></td><td>All expense records</td><td>Users</td></tr>
                <tr><td><strong>Activity logs</strong></td><td>Activity log and login sessions</td><td>Everything else</td></tr>
                <tr><td><strong>PA System</strong></td><td>All PA hires</td><td>Everything else</td></tr>
                <tr><td><strong>Ground Hire</strong></td><td>All ground hires</td><td>Everything else</td></tr>
                <tr><td><strong>All (Everything)</strong></td><td>All of the above combined</td><td><strong>User accounts and product catalogue</strong></td></tr>
            </tbody>
        </table>

        <h3>How to Reset</h3>
        <ol class="steps">
            <li>Click <strong>Reset System Data</strong> to open the confirmation dialog.</li>
            <li>Choose what to clear and which records (everything, or older than 30/90 days).</li>
            <li>Enter your <strong>admin password</strong> to confirm.</li>
            <li>Click <strong>Proceed with Reset</strong>.</li>
        </ol>
        <p>The reset runs in a database transaction — if anything fails partway through, nothing is deleted.</p>
        <p>Every reset is logged with the scope, summary of what was removed, and who did it.</p>

        <!-- 14 -->
        <h2 id="landing">14. Landing Page &amp; Privacy Policy</h2>

        <h3>Homepage</h3>
        <p>The public homepage (<code>index.html</code>) shows:</p>
        <ul>
            <li>The lounge logo and name in the navigation bar</li>
            <li><strong>Staff Login</strong> button</li>
            <li>A hero section with the logo, name, and tagline</li>
            <li>Two call-to-action buttons: <strong>Employee Portal Login</strong> and <strong>Install App</strong></li>
            <li>A "Our Services" section listing all five businesses (Bar &amp; Restaurant, Lodge &amp; Accommodation, Ground Hire, PA System Hire, Washing Bay)</li>
            <li>A "Contact Us" section with location, phone, WhatsApp, and email</li>
            <li>A footer with a link to the Privacy Policy</li>
        </ul>

        <h3>Install App (PWA)</h3>
        <p>The homepage can be installed on phones like a native app. The <strong>Install App</strong> button:</p>
        <ul>
            <li>On Android Chrome — triggers the native install prompt</li>
            <li>On iPhone Safari — shows step-by-step instructions to use "Add to Home Screen"</li>
            <li>On desktop Chrome/Edge — triggers the native install prompt</li>
        </ul>
        <p>Once installed, the app opens in its own window without browser chrome and works offline for basic pages.</p>

        <h3>Privacy Policy</h3>
        <p>A separate page explains what data the lounge collects (usernames, IP addresses, browser info for security), how it's used, and how to contact the business.</p>

        <!-- 15 -->
        <h2 id="tips">15. General Tips</h2>

        <h3>Colours on the screen</h3>
        <ul>
            <li><strong>Green</strong> — money coming in, healthy stock, success</li>
            <li><strong>Amber / Orange</strong> — attention needed (low stock, upcoming balance, warnings)</li>
            <li><strong>Red</strong> — money going out, dangerous actions, out-of-stock items</li>
        </ul>

        <h3>Working with the system</h3>
        <ul>
            <li><strong>Every primary action is a button.</strong> Green or amber buttons are safe. Red buttons usually require confirmation.</li>
            <li><strong>Always confirm</strong> when the system asks you to before doing something destructive. Deletions, resets, and force-logout cannot be undone.</li>
            <li><strong>Look for the "→" arrow</strong> on dashboard tiles — it means the tile is clickable and takes you to a detailed view.</li>
            <li><strong>On phones</strong>, tap the green <strong>↑ arrow</strong> at the bottom-right to jump back to the top of long lists.</li>
            <li><strong>Sign out properly</strong> when you leave a shared computer. If someone forgets, HR can force-logout you from the Active Sessions panel.</li>
            <li><strong>Use Export to PDF</strong> on any report before clearing data. Once reset, historical records are gone permanently.</li>
        </ul>

        <div class="manual-footer">
            <strong>Big Wallet Lounge</strong><br>
            Te-Cambia B, Bala Town Council, Kole District<br>
            0787 072 542 · +211 925 830 541 · <a href="mailto:info@bigwalletlounge.com">info@bigwalletlounge.com</a>
        </div>
    </main>
</div>

<!-- BACK TO TOP -->
<button class="back-top" id="backTop" type="button" aria-label="Move Up" title="Move Up">
    <span class="tip">Move Up</span>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 19V5"/>
        <path d="M5 12l7-7 7 7"/>
    </svg>
</button>

<script>
    // Highlight the current section in the TOC while scrolling
    (function () {
        const links = document.querySelectorAll('.toc a');
        const sections = [...links].map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);
        if (!sections.length) return;

        const setActive = (id) => {
            links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + id));
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) setActive(e.target.id);
            });
        }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });

        sections.forEach(s => observer.observe(s));
    })();

    // Back-to-top button
    (function () {
        const backTop = document.getElementById('backTop');
        if (!backTop) return;
        const toggleBackTop = () => backTop.classList.toggle('show', window.scrollY > 200);
        window.addEventListener('scroll', toggleBackTop, { passive: true });
        backTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        toggleBackTop();
    })();

    // Download PDF button - opens print dialog so user can "Save as PDF"
    (function () {
        const btn = document.getElementById('downloadPdf');
        if (!btn) return;
        btn.addEventListener('click', () => window.print());
    })();
</script>
</body>
</html>