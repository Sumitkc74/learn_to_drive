List<UserHistory> userHistoryFromJson(
        List<dynamic> userHistoryJson) =>
    List<UserHistory>.from(userHistoryJson.map((userHistoryJson) =>
        UserHistory.fromJson(userHistoryJson)));

class UserHistory {
  int? id;
  int? userId;
  String? attemptedQuestions;
  String? optionA;
  String? optionB;
  String? optionC;
  String? optionD;
  String? correctOptions;
  String? selectedOptions;
  String? createdAt;
  String? updatedAt;

  UserHistory(
      {this.id,
      this.userId,
      this.attemptedQuestions,
      this.optionA,
      this.optionB,
      this.optionC,
      this.optionD,
      this.correctOptions,
      this.selectedOptions,
      this.createdAt,
      this.updatedAt});

  UserHistory.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    userId = json['user_id'];
    attemptedQuestions = json['attempted_questions'];
    optionA = json['optionA'];
    optionB = json['optionB'];
    optionC = json['optionC'];
    optionD = json['optionD'];
    correctOptions = json['correct_options'];
    selectedOptions = json['selected_options'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['user_id'] = userId;
    data['attempted_questions'] = attemptedQuestions;
    data['optionA'] = optionA;
    data['optionB'] = optionB;
    data['optionC'] = optionC;
    data['optionD'] = optionD;
    data['correct_options'] = correctOptions;
    data['selected_options'] = selectedOptions;
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    return data;
  }
}