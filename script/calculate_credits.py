import pymysql
import pandas as pd

# 1️⃣ Connect to MySQL
conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',       # leave blank if no password
    database='faculty_appraisal'
)

# 2️⃣ Fetch feedback responses with faculty info
query = """
SELECT fb.faculty_id, fr.rating
FROM feedback_responses fr
JOIN feedback fb ON fr.feedback_id = fb.feedback_id
"""
df = pd.read_sql(query, conn)

if df.empty:
    print("No feedback data found.")
else:
    # 3️⃣ Calculate average rating per faculty
    avg_df = df.groupby('faculty_id')['rating'].mean().reset_index()
    avg_df['avg_score_percent'] = (avg_df['rating'] / 5) * 100

    # 4️⃣ Map average percentage to credits (0-4)
    def calculate_credits(percent):
        if percent >= 90:
            return 4
        elif percent >= 80:
            return 3
        elif percent >= 75:
            return 2
        else:
            return 1

    avg_df['credits'] = avg_df['avg_score_percent'].apply(calculate_credits)

    # 5️⃣ Insert or update faculty_credits table
    cursor = conn.cursor()
    for _, row in avg_df.iterrows():
        sql = """
        INSERT INTO faculty_credits (faculty_id, avg_score_percent, credits)
        VALUES (%s, %s, %s)
        ON DUPLICATE KEY UPDATE 
            avg_score_percent = VALUES(avg_score_percent),
            credits = VALUES(credits),
            calculated_on = CURRENT_TIMESTAMP
        """
        cursor.execute(sql, (row['faculty_id'], row['avg_score_percent'], row['credits']))

    conn.commit()
    cursor.close()
    print("Faculty credits updated successfully ✅")

conn.close()