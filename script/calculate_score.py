import pymysql
import json

# ---------------------- DATABASE CONNECTION ----------------------
conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='faculty_appraisal'  # change to your DB name
)
cursor = conn.cursor(pymysql.cursors.DictCursor)

# ---------------------- CREDITS CONFIG ----------------------
part_a = {
    1: (90, 4),
    2: (5, 3),
    3: (3, 2),
    4: (15, 1),
    5: (3, 2),
    6: (10, 1),
    7: (5, 2),
    8: (20, 2),
    9: (12, 3),
    10: (5, 2),
    11: (15, 1),
    12: (5, 2),
    13: {  # subquestions
        'p7': (4, 2),
        'p5_6': (9, 2),
        'p4': (8, 1),
        'p3': (10, 1)
    }
}

part_b = {
    1: (5, 6),
    2: (5, 5),
    3: (3, 3),
    4: (3, 3),
    5: (2, 4),
    6: (2, 2),
    7: (3, 4),
    8: (2, 5),
    9: (4, 4),
    10: (3, 4)
}

part_c = {
    1: (3, 3),
    2: (4, 2),
    3: (4, 2),
    4: (3, 2),
    5: (2, 2),
    6: (3, 1),
    7: (4, 2),
    8: (2, 3),
    9: (2, 2),
    10: (2, 1)
}

def capped_proportional(input_val, expected, max_credit):
    """Calculate Capped & Proportional Credit for responses."""
    return min((input_val / expected) * max_credit, max_credit)
def safe_int(val):
    try:
        return int(val)
    except (TypeError, ValueError):
        return 0


# ---------------------- FETCH ALL FACULTY APPRAISALS ----------------------
cursor.execute("SELECT * FROM faculty_appraisal")
faculties = cursor.fetchall()

for faculty in faculties:
    appraisal_id = faculty['appraisal_id']
    faculty_id = faculty['faculty_id']
    
    # Initialize responses score
    responses_score = 0
    
    # Fetch all responses for this appraisal
    cursor.execute("SELECT * FROM faculty_appraisal_responses WHERE appraisal_id=%s", (appraisal_id,))
    responses = cursor.fetchall()
    
    # Organize responses by part/question
    resp_dict = {}
    for r in responses:
        part = r['part']
        qno = r['question_no']
        resp_dict.setdefault(part, {})[qno] = r['answer']
    
    # ------ PART A ------
    for qno, val in part_a.items():
        if qno != 13:
            input_val = safe_int(resp_dict['A'].get(qno, 0))
            expected, max_credit = val
            responses_score += capped_proportional(input_val, expected, max_credit)
        else:
            # subquestions for Q13
            ans13 = resp_dict['A'].get(13)
            if ans13:
                try:
                    ans13 = json.loads(ans13)
                except:
                    ans13 = {}
                for sub, (expected, max_credit) in val.items():
                    input_val = safe_int(ans13.get(sub, 0))
                    responses_score += capped_proportional(input_val, expected, max_credit)
    
    # ------ PART B ------
    for qno, (expected, max_credit) in part_b.items():
        input_val = safe_int(resp_dict['B'].get(qno, 0))
        responses_score += capped_proportional(input_val, expected, max_credit)
    
    # ------ PART C ------
    for qno, (expected, max_credit) in part_c.items():
        input_val = safe_int(resp_dict['C'].get(qno, 0))
        responses_score += capped_proportional(input_val, expected, max_credit)
    
    # Ensure responses_score column exists
    cursor.execute("ALTER TABLE faculty_appraisal ADD COLUMN IF NOT EXISTS responses_score FLOAT")
    cursor.execute(
        "UPDATE faculty_appraisal SET responses_score=%s WHERE appraisal_id=%s",
        (responses_score, appraisal_id)
    )
    
    # ------ FINAL SCORE (only if HOD/Principal feedback exists) ------
    final_feedback_db = faculty['hod_or_principal_feedback']  # keep None if not filled
    if final_feedback_db is not None:  # calculate final_score only if feedback is filled
        final_feedback = float(final_feedback_db)
        
        # Fetch student feedback credit from faculty_credits table
        cursor.execute("SELECT credits FROM faculty_credits WHERE faculty_id=%s", (faculty_id,))
        credit_row = cursor.fetchone()
        student_credit = float(credit_row['credits']) if credit_row else 0

        final_score = responses_score + student_credit + final_feedback

        # Ensure final_score column exists
        cursor.execute("ALTER TABLE faculty_appraisal ADD COLUMN IF NOT EXISTS final_score FLOAT")
        cursor.execute(
            "UPDATE faculty_appraisal SET final_score=%s WHERE appraisal_id=%s",
            (final_score, appraisal_id)
        )

# Commit changes and close
conn.commit()
cursor.close()
conn.close()

print("Responses score and final score calculated and updated successfully!")