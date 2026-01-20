-- Students
INSERT INTO students (roll_no, name, department, year, section, password) VALUES
('22CSE001', 'Amit Kumar', 'CSE', 2, 'A', '$2y$10$abcdefg...'), -- Replace with real hash!
('22CSE002', 'Priya Singh', 'CSE', 2, 'A', '$2y$10$abcdefg...'),
('22ECE003', 'Sujithra M', 'ECE', 2, 'B', '$2y$10$abcdefg...');

-- Faculty
INSERT INTO faculty (faculty_name, department, designation) VALUES
('Dr. Ramesh Rao', 'CSE', 'Professor'),
('Ms. Kavitha Sekar', 'ECE', 'Assistant Professor'),
('Mr. Sanjay Mehra', 'CSE', 'Associate Professor');

-- Subjects
INSERT INTO subjects (subject_code, subject_name, department, year) VALUES
('CS201', 'Data Structures', 'CSE', 2),
('CS202', 'Database Systems', 'CSE', 2),
('EC201', 'Analog Electronics', 'ECE', 2);

-- Faculty-Subject Mapping
INSERT INTO faculty_subject_mapping (faculty_id, subject_id, department, year, section) VALUES
(1, 1, 'CSE', 2, 'A'),
(3, 2, 'CSE', 2, 'A'),
(2, 3, 'ECE', 2, 'B');

-- Feedback Questions (your 10 questions)
INSERT INTO feedback_questions (question_text) VALUES
('The faculty explains concepts clearly and in an understandable manner.'),
('The faculty is well-prepared and organized for lectures/labs.'),
('The teaching methods (examples, demonstrations, activities) used by the faculty make learning effective.'),
('The faculty encourages questions, discussions, and clarifies doubts.'),
('The faculty provides timely feedback on assignments, exams, and projects.'),
('The faculty motivates and guides students for higher learning (online courses, certifications, projects, etc.).'),
('The faculty supports and encourages participation in extracurricular/technical activities.'),
('The faculty is approachable and available for academic/career guidance outside class hours.'),
('The faculty maintains fairness and transparency in evaluation and grading.'),
('The faculty uses digital tools/technology (LMS, PPT, online resources) effectively to enhance learning.');



-- Example: Adding 5 faculties per department including HOD and HRM

-- CSE Department
INSERT INTO faculty (faculty_name, department, designation) VALUES
('Dr. Anil Kumar', 'CSE', 'Professor'),
('Ms. Sneha Reddy', 'CSE', 'Associate Professor'),
('Mr. Ravi Patel', 'CSE', 'Assistant Professor'),
('Dr. Suresh Rao', 'CSE', 'HOD'),
('Ms. Ananya Singh', 'CSE', 'HRM');

-- IT Department
INSERT INTO faculty (faculty_name, department, designation) VALUES
('Dr. Rohit Sharma', 'IT', 'Professor'),
('Ms. Priya Menon', 'IT', 'Associate Professor'),
('Mr. Arjun Das', 'IT', 'Assistant Professor'),
('Dr. Meena Iyer', 'IT', 'HOD'),
('Ms. Kavya Joshi', 'IT', 'HRM');

-- ECE Department
INSERT INTO faculty (faculty_name, department, designation) VALUES
('Dr. Ramesh Rao', 'ECE', 'Professor'),
('Ms. Kavitha Sekar', 'ECE', 'Associate Professor'),
('Mr. Sanjay Mehra', 'ECE', 'Assistant Professor'),
('Dr. Rekha Nair', 'ECE', 'HOD'),
('Ms. Aishwarya Pillai', 'ECE', 'HRM');

-- MECH Department
INSERT INTO faculty (faculty_name, department, designation) VALUES
('Dr. Vijay Kumar', 'MECH', 'Professor'),
('Ms. Neha Gupta', 'MECH', 'Associate Professor'),
('Mr. Rohit Verma', 'MECH', 'Assistant Professor'),
('Dr. Sunita Sharma', 'MECH', 'HOD'),
('Mr. Arvind Rao', 'MECH', 'HRM');

--CIVIL Department
INSERT INTO faculty (faculty_name, department, designation) VALUES
('Shalini', 'CIVIL', 'Professor'),
('Ragavi', 'CIVIL', 'Associate Professor'),
('Gayathri', 'CIVIL', 'Assistant Professor'),
('Karthika', 'CIVIL', 'HOD'),
('Mithra', 'CIVIL', 'HRM');

INSERT INTO faculty (faculty_name, department, designation) VALUES
('Mithu', 'AI', 'Professor'),
('Sreejith', 'AI', 'Associate Professor'),
('Varna', 'AI', 'Assistant Professor'),
('Diya', 'AI', 'HOD'),
('Yazh', 'AI', 'HRM');

-- Assign simple usernames & passwords
INSERT INTO faculty_roles (faculty_id, role, department, username, password) VALUES
(1, 'Professor', 'CSE', 'cse1', 'pass1'),
(2, 'Associate Professor', 'CSE', 'cse2', 'pass2'),
(3, 'Assistant Professor', 'CSE', 'cse3', 'pass3'),
(4, 'HOD', 'CSE', 'cseh', 'pass4'),
(5, 'HRM', 'CSE', 'cser', 'pass5'),

(6, 'Professor', 'IT', 'it1', 'pass6'),
(7, 'Associate Professor', 'IT', 'it2', 'pass7'),
(8, 'Assistant Professor', 'IT', 'it3', 'pass8'),
(9, 'HOD', 'IT', 'ith', 'pass9'),
(10, 'HRM', 'IT', 'itr', 'pass10'),

(11, 'Professor', 'ECE', 'ece1', 'pass11'),
(12, 'Associate Professor', 'ECE', 'ece2', 'pass12'),
(13, 'Assistant Professor', 'ECE', 'ece3', 'pass13'),
(14, 'HOD', 'ECE', 'eceh', 'pass14'),
(15, 'HRM', 'ECE', 'ecer', 'pass15'),

(16, 'Professor', 'MECH', 'mech1', 'pass16'),
(17, 'Associate Professor', 'MECH', 'mech2', 'pass17'),
(18, 'Assistant Professor', 'MECH', 'mech3', 'pass18'),
(19, 'HOD', 'MECH', 'mechh', 'pass19'),
(20, 'HRM', 'MECH', 'mechr', 'pass20'),

(23, 'Professor', 'CIVIL', 'civil1', 'pass23'),
(24, 'Associate Professor', 'CIVIL', 'civil2', 'pass24'),
(25, 'Assistant Professor', 'CIVIL', 'civil3', 'pass25'),
(26, 'HOD', 'CIVIL', 'civilh', 'pass26'),
(27, 'HRM', 'CIVIL', 'civilr', 'pass27'),

(28, 'Professor', 'Professor','None', 'AI', 'ai1', 'pass28'),
(29, 'Associate Professor', 'Associate Professor', 'None', 'AI', 'ai2', 'pass29'),
(30, 'Assistant Professor', 'Assistant Professor', 'None', 'AI', 'ai3', 'pass30'),
(31, 'HOD', 'Professor', 'HOD', 'AI', 'aih', 'pass31'),
(32, 'HRM', 'Associate Professor', 'HRM', 'AI', 'air', 'pass32');

--credit manual inserting--

INSERT INTO faculty_credits (faculty_id, avg_score_percent, credits) VALUES
(1, 85.50, 4),
(2, 78.20, 3),
(3, 90.00, 4),
(6, 72.50, 3),
(7, 68.00, 2),
(8, 95.00, 4),
(11, 80.75, 3);


-- Example: insert a principal with manual password
INSERT INTO principal (principal_name, username, password, department) VALUES
('Dr. Rajesh Kumar', 'principal1', 'principal123', NULL);

-- for status work in hod -> principal issue
ALTER TABLE faculty_appraisal 
  MODIFY COLUMN status ENUM('Pending', 'Forwarded', 'Forwarded_Principal', 'Verified') 
  DEFAULT 'Pending';


-- Insert the default IQAC user
INSERT INTO iqac_user (username, password) VALUES 
('iqac', 'iqac123'); -- you can change password, later hash with password_hash()

-- for status = finalized after iqac
ALTER TABLE faculty_appraisal 
MODIFY COLUMN status ENUM('Pending', 'Forwarded', 'Forwarded_Principal', 'Verified', 'finalized') 
DEFAULT 'Pending';


-- insert default HR login (change password later or hash it)
INSERT INTO hr_user (username, password) VALUES 
('hr', 'hr123');