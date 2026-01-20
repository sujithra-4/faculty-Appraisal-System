
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    roll_no VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    section VARCHAR(5) NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE faculty (
    faculty_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    designation VARCHAR(50)
);

CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    year INT NOT NULL
);

CREATE TABLE faculty_subject_mapping (
    mapping_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    subject_id INT NOT NULL,
    department VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    section VARCHAR(5) NOT NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
);

CREATE TABLE feedback_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    question_text VARCHAR(255) NOT NULL
);

CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    faculty_id INT NOT NULL,
    subject_id INT NOT NULL,
    submitted_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    locked BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
);

CREATE TABLE feedback_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL,
    question_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    FOREIGN KEY (feedback_id) REFERENCES feedback(feedback_id),
    FOREIGN KEY (question_id) REFERENCES feedback_questions(question_id)
);

CREATE TABLE faculty_credits (
    credit_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    avg_score_percent DECIMAL(5,2) NOT NULL,
    credits INT NOT NULL CHECK (credits BETWEEN 0 AND 4),
    calculated_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id)
);




CREATE TABLE principal (
    principal_id INT AUTO_INCREMENT PRIMARY KEY,
    principal_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,  -- plain text password
    department VARCHAR(50) DEFAULT NULL,
    created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);




CREATE TABLE iqac (
    iqac_id INT AUTO_INCREMENT PRIMARY KEY,
    iqac_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL
);


CREATE TABLE hr (
    hr_id INT AUTO_INCREMENT PRIMARY KEY,
    hr_name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Faculty Appraisal Tables

-- Part A/B/C Appraisal Entries
CREATE TABLE faculty_appraisal (
    appraisal_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    department VARCHAR(50) NOT NULL,
    appraisal_year YEAR NOT NULL,
    status ENUM('Pending','Forwarded','Verified') DEFAULT 'Pending',
    submitted_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id)
);

CREATE TABLE faculty_appraisal_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    appraisal_id INT NOT NULL,
    part ENUM('A','B','C') NOT NULL,
    question_no INT NOT NULL,
    answer VARCHAR(255) NOT NULL,
    FOREIGN KEY (appraisal_id) REFERENCES faculty_appraisal(appraisal_id)
);

-- Faculty Roles Table (to distinguish HOD, HRM, etc.)
CREATE TABLE faculty_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    role ENUM('Professor','Associate Professor','Assistant Professor','HOD','HRM') NOT NULL,
    department VARCHAR(50) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id)
);


--hod and hrm as prof, asso prof, ass prof
ALTER TABLE faculty_roles
ADD COLUMN designation ENUM('Professor','Associate Professor','Assistant Professor') AFTER role,
ADD COLUMN admin_role ENUM('HOD','HRM', 'None') DEFAULT 'None' AFTER designation;


-- CSE
UPDATE faculty_roles SET designation='Professor', admin_role='None' WHERE faculty_id=1;
UPDATE faculty_roles SET designation='Associate Professor', admin_role='None' WHERE faculty_id=2;
UPDATE faculty_roles SET designation='Assistant Professor', admin_role='None' WHERE faculty_id=3;
UPDATE faculty_roles SET designation='Professor', admin_role='HOD' WHERE faculty_id=4;
UPDATE faculty_roles SET designation='Associate Professor', admin_role='HRM' WHERE faculty_id=5;

-- IT
UPDATE faculty_roles SET designation='Professor', admin_role='None' WHERE faculty_id=6;
UPDATE faculty_roles SET designation='Associate Professor', admin_role='None' WHERE faculty_id=7;
UPDATE faculty_roles SET designation='Assistant Professor', admin_role='None' WHERE faculty_id=8;
UPDATE faculty_roles SET designation='Professor', admin_role='HOD' WHERE faculty_id=9;
UPDATE faculty_roles SET designation='Associate Professor', admin_role='HRM' WHERE faculty_id=10;

-- ECE
UPDATE faculty_roles SET designation='Professor', admin_role='None' WHERE faculty_id=11;
UPDATE faculty_roles SET designation='Associate Professor', admin_role='None' WHERE faculty_id=12;
UPDATE faculty_roles SET designation='Assistant Professor', admin_role='None' WHERE faculty_id=13;
UPDATE faculty_roles SET designation='Professor', admin_role='HOD' WHERE faculty_id=14;
UPDATE faculty_roles SET designation='Associate Professor', admin_role='HRM' WHERE faculty_id=15;

-- MECH
UPDATE faculty_roles SET designation='Professor', admin_role='None' WHERE faculty_id=16;
UPDATE faculty_roles SET designation='Associate Professor', admin_role='None' WHERE faculty_id=17;
UPDATE faculty_roles SET designation='Assistant Professor', admin_role='None' WHERE faculty_id=18;
UPDATE faculty_roles SET designation='Professor', admin_role='HOD' WHERE faculty_id=19;
UPDATE faculty_roles SET designation='Associate Professor', admin_role='HRM' WHERE faculty_id=20;

--score calculation column
ALTER TABLE faculty_appraisal
ADD COLUMN responses_score FLOAT DEFAULT 0,
ADD COLUMN final_score FLOAT DEFAULT 0;


CREATE TABLE IF NOT EXISTS iqac_user (
    iqac_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL -- store hashed password later
);

CREATE TABLE IF NOT EXISTS hr_user (
    hr_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

--- proof upload

ALTER TABLE faculty_appraisal_responses
ADD COLUMN upload_files TEXT NULL AFTER answer;

