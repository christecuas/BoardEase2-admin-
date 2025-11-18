# Payment Report Guide

## How to Access the Payment Report

### Step 1: Open Admin Dashboard
1. Log in to the admin dashboard
2. Navigate to the **Reports** section
3. Look for the **Payment Reports** card
4. Click the **"Download Payment Report"** button

### Step 2: Download the Report
- The report will automatically download as a CSV file
- The filename will be: `payment_report_YYYY-MM-DD_HH-MM-SS.csv`
- The file will be saved to your browser's default download folder

## How to Open the CSV File

### Option 1: Microsoft Excel
1. Open Microsoft Excel
2. Click **File** → **Open**
3. Navigate to your Downloads folder
4. Select the CSV file
5. Excel will automatically parse the CSV into columns
6. **Tip:** You can use Excel's filters and sorting features to analyze the data

### Option 2: Google Sheets
1. Go to [Google Sheets](https://sheets.google.com)
2. Click **File** → **Import**
3. Select **Upload** tab
4. Choose the CSV file from your Downloads folder
5. Select **Import location: Create new spreadsheet**
6. Click **Import data**
7. **Tip:** Google Sheets allows you to share and collaborate on the report

### Option 3: LibreOffice Calc (Free Alternative)
1. Open LibreOffice Calc
2. Click **File** → **Open**
3. Select the CSV file
4. In the Text Import dialog, select:
   - Separator: **Comma**
   - Text delimiter: **"** (double quote)
5. Click **OK**

## Understanding the Report Columns

The payment report contains the following columns (in order):

### Basic Payment Information
1. **Payment ID** - Unique identifier for each payment
2. **Booking ID** - The booking this payment belongs to
3. **Amount** - Payment amount (formatted with 2 decimal places)
4. **Method** - Payment method (Cash, GCash, Bank Transfer, Check)
5. **Status (Actual)** - **IMPORTANT:** Actual payment status calculated from payment breakdowns (source of truth)
   - `Fully Paid` - All payment breakdown periods are paid
   - `Completed/Partially` - Some payment breakdown periods are paid
   - `Pending` - No payment breakdown periods are paid
6. **Status (Payment Table)** - Payment status stored in the payments table (for reference)

### Date Information
7. **Date** - Payment date and time
8. **Month** - Payment month (format: YYYY-MM)
9. **Year** - Payment year
10. **Month Number** - Month number (1-12)

### Payment Details
11. **Monthly Payment** - Yes/No (whether this is a monthly payment)
12. **Months Paid** - Number of months paid
13. **Total Months** - Total months required for the booking
14. **Notes** - Additional payment notes
15. **Receipt URL** - URL to the payment receipt (if available)
16. **Payment Proof** - Payment proof document/image (if available)

### Customer Information
17. **Customer Name** - Full name of the boarder (first name, middle name, last name, suffix)
18. **Email** - Boarder's email address
19. **Phone** - Boarder's phone number

### Owner Information
20. **Owner Name** - Full name of the boarding house owner
21. **Owner Email** - Owner's email address
22. **Owner Phone** - Owner's phone number

### Booking Information
23. **Booking Start Date** - Start date of the booking
24. **Booking End Date** - End date of the booking
25. **Booking Status** - Status of the booking (Pending, Confirmed, Cancelled, etc.)
26. **Booking Date** - Date when the booking was created

### Room Information
27. **Boarding House** - Name of the boarding house
28. **Room Category** - Room category (Monthly, Daily, etc.)
29. **Room Name** - Name of the room
30. **Room Number** - Room number (if applicable)
31. **Room Price** - Price of the room

### Payment Breakdown Information (NEW - Most Important!)
32. **Total Breakdowns** - Total number of payment breakdown periods for this booking
33. **Paid Breakdowns** - Number of payment breakdown periods that have been paid
34. **Unpaid Breakdowns** - Number of payment breakdown periods that are still unpaid
35. **Paid Amount** - Total amount paid across all breakdown periods
36. **Unpaid Amount** - Total amount still unpaid across all breakdown periods
37. **Periods Covered** - List of periods covered (e.g., "1st month, 2nd month, 3 days")

## Key Concepts

### Payment Breakdowns (Source of Truth)
- **Payment breakdowns** are the periods that make up a payment (e.g., "1st month", "2nd month", "3 days")
- Each breakdown period has its own status (`is_paid` = 1 or 0)
- The **actual payment status** is calculated from these breakdowns:
  - If ALL breakdowns are paid → `Fully Paid`
  - If SOME breakdowns are paid → `Completed/Partially`
  - If NO breakdowns are paid → `Pending`

### Why Two Status Columns?
- **Status (Actual)** - Calculated from payment breakdowns (the true status)
- **Status (Payment Table)** - Stored in the payments table (may be outdated)
- **Always use "Status (Actual)" for accurate information!**

## How to Analyze the Report

### 1. Filter by Payment Status
- In Excel/Google Sheets, use the filter function on the "Status (Actual)" column
- Filter for `Pending` to see unpaid payments
- Filter for `Completed/Partially` to see partially paid bookings
- Filter for `Fully Paid` to see completed payments

### 2. Sort by Date
- Sort by "Date" column to see recent payments first
- Sort by "Booking Start Date" to see upcoming bookings

### 3. Calculate Totals
- Use Excel's SUM function on the "Paid Amount" column to see total paid
- Use SUM on the "Unpaid Amount" column to see total outstanding
- Compare "Paid Breakdowns" vs "Total Breakdowns" to see payment progress

### 4. Group by Owner
- Use pivot tables (Excel) or group functions to analyze payments by owner
- See which owners have the most payments
- Identify owners with outstanding payments

### 5. Identify Issues
- Look for payments where "Status (Actual)" ≠ "Status (Payment Table)" (data inconsistency)
- Check for bookings with "Unpaid Breakdowns" > 0 (outstanding payments)
- Identify payments with "Unpaid Amount" > 0 (need attention)

## Common Questions

### Q: Why are there multiple payments for the same booking?
**A:** A booking can have multiple payments over time. Each payment may cover different breakdown periods.

### Q: What if "Total Breakdowns" is 0?
**A:** This means no payment breakdowns have been created for this booking yet. The status will use the payment table status.

### Q: What does "Periods Covered" show?
**A:** It shows all the payment breakdown periods (e.g., "1st month, 2nd month, 3 days") that exist for this booking, regardless of whether they're paid or not.

### Q: How do I know if a payment is complete?
**A:** Check the "Status (Actual)" column:
- If it says `Fully Paid` → All breakdown periods are paid
- If it says `Completed/Partially` → Some periods are paid, some are not
- If it says `Pending` → No periods are paid yet

### Q: Can I export this to PDF?
**A:** Yes! In Excel or Google Sheets, you can:
1. Select all the data
2. Format it nicely
3. Go to **File** → **Print** or **File** → **Download** → **PDF**

## Tips for Best Results

1. **Always use Excel or Google Sheets** - These tools handle CSV files best and provide filtering/sorting capabilities
2. **Freeze the first row** - In Excel, go to **View** → **Freeze Panes** → **Freeze Top Row** to keep column headers visible while scrolling
3. **Use filters** - Click the filter icon in Excel/Sheets to easily filter by status, owner, date, etc.
4. **Create pivot tables** - Use Excel's pivot table feature to summarize data by owner, status, or date
5. **Format as table** - In Excel, select all data and go to **Home** → **Format as Table** for better visualization

## Troubleshooting

### Problem: CSV file opens with all data in one column
**Solution:** 
- In Excel: Go to **Data** → **Text to Columns** → Select **Delimited** → Choose **Comma** as delimiter
- In Google Sheets: The import dialog should automatically detect commas

### Problem: Special characters (like ₱) don't display correctly
**Solution:** 
- Make sure you're using UTF-8 encoding
- In Excel: Go to **Data** → **Get External Data** → **From Text** → Choose **UTF-8** encoding

### Problem: Report shows old data
**Solution:** 
- Generate a new report by clicking "Download Payment Report" again
- The report is generated at the time of download, so it reflects the current database state

## Need Help?

If you have questions about the payment report or need assistance interpreting the data, please contact the system administrator.

---

**Last Updated:** 2025-01-13
**Report Format:** CSV (Comma-Separated Values)
**Encoding:** UTF-8



