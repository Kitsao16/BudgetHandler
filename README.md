# BudgetHandler

BudgetHandler is a comprehensive financial management tool designed to help users manage their budgets efficiently. This project showcases my prompt engineering skills using AI tools, allowing me to efficiently structure problem-solving processes and generate optimized code even with unfamiliar languages like PHP. The project was completed within one week.

## Features

- **User Authentication:**
  - Secure login system to authenticate users.
  - Registration page to allow new users to sign up.

- **User Management:**
  - Stores user information in a MySQL database, including first and last names.
  - Displays the user’s name upon login.

- **Budget Management:**
  - Users can set their total budget, which initializes the remaining budget.
  - Functionality to add budget items with associated costs.
  - Budget validation to ensure the remaining budget is sufficient before adding new items.
  - Displays a list of budget items along with their costs and the remaining budget in real-time.

- **Responsive Design:**
  - Media queries ensure the application is responsive across different screen sizes.
  - Maintains the original layout and readability even when the browser window is resized to a very small width.

- **Error Handling:**
  - Error messages for invalid budget amounts and insufficient budget scenarios.
  - Success messages upon successful addition of budget items.

- **Database Interaction:**
  - Utilizes MySQL for database operations, including inserting and retrieving user and budget data.
  - Secure data handling through prepared statements to prevent SQL injection attacks.

- **Navigation and Usability:**
  - Navigation buttons for easy access to different sections of the application.
  - User-friendly interface with clear labels and instructions.

## Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/Kitsao16/BudgetHandler.git
   cd BudgetHandler

