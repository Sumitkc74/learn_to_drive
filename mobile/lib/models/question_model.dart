List<Question> attemptedQuestions = [];

List<Question> questionFromJson(
        List<dynamic> questionJson) =>
    List<Question>.from(questionJson.map((questionJson) =>
        Question.fromJson(questionJson)));

class Question {
  int? id;
  String? question;
  String? option1;
  String? option2;
  String? option3;
  String? option4;
  String? correctOption;
  String? selectOption;
  String? createdAt;
  String? updatedAt;

  Question(
      {this.id,
      this.question,
      this.option1,
      this.option2,
      this.option3,
      this.option4,
      this.correctOption,
      this.selectOption,
      this.createdAt,
      this.updatedAt});

  Question.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    question = json['question'];
    option1 = json['option1'];
    option2 = json['option2'];
    option3 = json['option3'];
    option4 = json['option4'];
    correctOption = json['correctOption'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['question'] = question;
    data['option1'] = option1;
    data['option2'] = option2;
    data['option3'] = option3;
    data['option4'] = option4;
    data['correctOption'] = correctOption;
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    return data;
  }
}