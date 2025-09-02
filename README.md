Key Features:

1.User Authentication:
The system requires users to log in, with session management to ensure only authenticated users can access their profiles.
A logout button is provided to end the session and redirect to the login page (index.html).

2.Course Management:
Normal Courses: Users can add courses with detailed grading components, including four Class Tests (CTs), final exam scores, attendance, and assignments. The system calculates the total marks and assigns a grade based on a predefined scale (e.g., A+ for ≥80, F for <40).
Sessional Courses: Users can add courses with only a grade and credit value, suitable for courses without detailed assessments.
Edit Functionality: Users can modify existing course details, including marks or grades, and update them in the database.
Delete Functionality: Courses can be removed from the record as needed.

3.Grade and CGPA Calculation:
The system maps grades to GPA values (e.g., A+ = 4.0, F = 0.0) and calculates the CGPA by weighting the GPA by course credits.
It uses the best three out of four CT scores for normal courses to compute the average, ensuring fairness in grading.
The CGPA is displayed prominently on the profile page.

4.Data Storage:
The application uses a MySQL database to store user data, including course details, grades, and credits, with tables for normal courses (courses) and sessional courses (extra_courses).
Prepared statements are used to prevent SQL injection and ensure secure data handling.

User Interface:
The interface is styled with CSS, featuring a gradient background, responsive forms, and tables to display course data.
Interactive elements include buttons for adding, editing, and deleting courses, with hover effects for better user experience.
The layout is centered and includes a header with a logout option.
