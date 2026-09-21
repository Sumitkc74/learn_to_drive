// Include the trailing /api/. Android emulator reaches the host at 10.0.2.2.
const String baseURL = String.fromEnvironment('API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/');
// ApiClient adds the bearer token only after validating the destination.
const Map<String, String> headers = {
  'Accept': 'application/json',
  'Content-Type': 'application/json',
};

const String loginAPI = '${baseURL}auth/login';
const String registerAPI = '${baseURL}auth/register';
const String changePasswordAPI = '${baseURL}auth/password';
const String updateProfileAPI = '${baseURL}auth/updateProfile';
const String logoutAPI = '${baseURL}auth/logout';

const String trafficSignAPI = '${baseURL}trafficSign';
const String visionTestAPI = '${baseURL}visionTest';
const String examPaperAPI = '${baseURL}examPaper';
const String examInformationAPI = '${baseURL}examInformation';
const String tutorialAPI = '${baseURL}tutorial';
const String noticeAPI = '${baseURL}notice';
const String questionAPI = '${baseURL}question';
const String userHistoryAPI = '${baseURL}userHistory';
